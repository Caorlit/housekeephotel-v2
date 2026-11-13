<?php

namespace App\Services;

use App\Models\DayGeneration;
use App\Models\Hotel;
use App\Models\RoomDay;
use App\Models\Stay;
use App\Support\CurrentHotel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Dalle prenotazioni alle camere da pulire.
 *
 * - genera(): crea le camere di un giorno (Cambio/Riassetto) UNA volta sola;
 *   dopo, le modifiche a mano della reception restano (es. riassetto tolto).
 * - sincronizza(): quando una prenotazione cambia, riallinea i giorni già generati,
 *   senza toccare camere iniziate, con segnalazioni o inserite a mano.
 * - Le camere generate vanno all'ultima addetta che ha pulito quella camera.
 *
 * Lavora sull'hotel attivo (CurrentHotel): chi la chiama deve averlo impostato.
 */
class GeneratoreGiornate
{
    public function __construct(private CurrentHotel $current) {}

    /** @return int camere create */
    public function genera(Hotel $hotel, string $data, bool $forza = false): int
    {
        $this->assicuraHotel($hotel);

        if (! $forza && DayGeneration::whereDate('data', $data)->exists()) {
            return 0;
        }

        try {
            return DB::transaction(function () use ($data) {
                $presenti = RoomDay::delGiorno($data)->pluck('room_id')->flip();
                $create = 0;

                foreach (Stay::attiviIl($data)->with('room')->orderBy('id')->get() as $stay) {
                    $tipo = $stay->pulizia($data);
                    if ($tipo === null || $presenti->has($stay->room_id) || ! $stay->room?->attiva) {
                        continue;
                    }
                    $this->crea($stay, $data, $tipo);
                    $presenti[$stay->room_id] = true;
                    $create++;
                }

                if (! DayGeneration::whereDate('data', $data)->exists()) {
                    DayGeneration::create(['data' => $data]);
                }

                return $create;
            });
        } catch (UniqueConstraintViolationException) {
            return 0; // generato nello stesso istante da un'altra richiesta
        }
    }

    /**
     * Riallinea i giorni già generati (da oggi in poi) di una camera in un intervallo.
     * $escludi: prenotazione che sta per essere cancellata (non va più considerata).
     */
    public function sincronizza(Hotel $hotel, int $roomId, string $dal, string $al, ?int $escludi = null): void
    {
        $this->assicuraHotel($hotel);
        $dal = max($dal, $hotel->oggi());
        if ($dal > $al) {
            return;
        }

        $giorni = DayGeneration::whereDate('data', '>=', $dal)->whereDate('data', '<=', $al)
            ->get()->map(fn ($g) => $g->data->toDateString());

        foreach ($giorni as $data) {
            [$tipo, $stay] = $this->attesa($roomId, $data, $escludi);
            $rd = RoomDay::delGiorno($data)->where('room_id', $roomId)->withCount('issues')->first();

            if ($rd === null) {
                if ($stay !== null) {
                    $this->crea($stay, $data, $tipo);
                }

                continue;
            }

            // Inserita a mano, già iniziata o con segnalazioni: non si tocca.
            $modificabile = $rd->stay_id !== null && $rd->stato === RoomDay::DA_FARE && $rd->issues_count === 0;
            if (! $modificabile) {
                continue;
            }

            if ($stay === null) {
                $rd->delete();
            } elseif ($rd->tipo !== $tipo || $rd->stay_id !== $stay->id) {
                $rd->update(['tipo' => $tipo, 'stay_id' => $stay->id]);
            }
        }
    }

    /**
     * L'ultima addetta (ancora attiva) che ha pulito la camera prima di quel giorno.
     * Le sostituzioni (giorni liberi) non contano: la camera torna alla titolare.
     */
    public function addettaPer(int $roomId, string $data): ?int
    {
        return RoomDay::where('room_id', $roomId)
            ->whereDate('data', '<', $data)
            ->whereNotNull('staff_id')
            ->where('sostituzione', false)
            ->whereHas('staff', fn ($q) => $q->where('attivo', true))
            ->orderByDesc('data')
            ->value('staff_id');
    }

    /** @return array{0: ?string, 1: ?Stay} */
    private function attesa(int $roomId, string $data, ?int $escludi): array
    {
        $soggiorni = Stay::attiviIl($data)->where('room_id', $roomId)
            ->when($escludi, fn ($q) => $q->whereKeyNot($escludi))
            ->orderBy('id')->get();

        foreach ($soggiorni as $stay) {
            if ($tipo = $stay->pulizia($data)) {
                return [$tipo, $stay];
            }
        }

        return [null, null];
    }

    private function crea(Stay $stay, string $data, string $tipo): RoomDay
    {
        return RoomDay::create([
            'room_id' => $stay->room_id,
            'stay_id' => $stay->id,
            'data' => $data,
            'tipo' => $tipo,
            'staff_id' => $this->addettaPer($stay->room_id, $data),
        ]);
    }

    private function assicuraHotel(Hotel $hotel): void
    {
        if ($this->current->id() !== $hotel->id) {
            $this->current->set($hotel);
        }
    }
}
