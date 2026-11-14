<?php

namespace App\Services;

use App\Models\DayGeneration;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\Stay;
use Carbon\CarbonImmutable;

/**
 * Le camere previste per un'addetta nei prossimi giorni (solo da guardare).
 *
 * Per ogni giorno:
 * - se la reception ha già preparato quel giorno, vale quello che c'è;
 * - altrimenti: camere già inserite a mano + quelle che nasceranno dalle
 *   prenotazioni, assegnate (come farà il generatore) all'ultima titolare della camera.
 *
 * Lavora sull'hotel attivo (lo imposta il middleware dell'addetta).
 */
class Previsione
{
    public const GIORNI = 7;

    /** @return list<array{data: string, etichetta: string, camere: list<array{numero: string, tipo: string}>}> */
    public function perAddetta(Staff $staff, int $giorni = self::GIORNI): array
    {
        $oggi = CarbonImmutable::parse($staff->hotel->oggi());
        $primo = $oggi->addDay()->toDateString();
        $ultimo = $oggi->addDays($giorni)->toDateString();

        $generati = DayGeneration::whereDate('data', '>=', $primo)->whereDate('data', '<=', $ultimo)
            ->get()->map(fn ($g) => $g->data->toDateString())->flip();

        // Giorni futuri già presenti (a mano o generati), per data.
        $presenti = RoomDay::whereDate('data', '>=', $primo)->whereDate('data', '<=', $ultimo)
            ->with('room')->get()->groupBy(fn ($g) => $g->data->toDateString());

        // Storico delle titolari per camera (senza sostituzioni), per prevedere a chi andrà.
        $titolari = RoomDay::whereDate('data', '>=', $oggi->subDays(90)->toDateString())
            ->whereDate('data', '<=', $ultimo)
            ->whereNotNull('staff_id')->where('sostituzione', false)
            ->whereHas('staff', fn ($q) => $q->where('attivo', true))
            ->orderBy('data')->get(['room_id', 'data', 'staff_id'])
            ->groupBy('room_id');

        $soggiorni = Stay::whereDate('arrivo', '<', $ultimo)->whereDate('partenza', '>=', $primo)
            ->with('room')->get();

        $risultato = [];
        for ($i = 1; $i <= $giorni; $i++) {
            $giorno = $oggi->addDays($i);
            $data = $giorno->toDateString();
            $delGiorno = $presenti->get($data, collect());

            $camere = $delGiorno->where('staff_id', $staff->id)
                ->map(fn ($g) => ['numero' => $g->room->numero, 'piano' => $g->room->piano, 'tipo' => $g->tipo])
                ->values()->all();

            if (! $generati->has($data)) {
                $occupate = $delGiorno->pluck('room_id')->flip();
                foreach ($soggiorni as $s) {
                    $tipo = $s->pulizia($data);
                    if ($tipo === null || $occupate->has($s->room_id) || ! $s->room?->attiva) {
                        continue;
                    }
                    if ($this->titolare($titolari->get($s->room_id), $data) === $staff->id) {
                        $camere[] = ['numero' => $s->room->numero, 'piano' => $s->room->piano, 'tipo' => $tipo];
                    }
                }
            }

            usort($camere, fn ($a, $b) => [$a['piano'], strlen($a['numero']), $a['numero']] <=> [$b['piano'], strlen($b['numero']), $b['numero']]);

            $risultato[] = [
                'data' => $data,
                'etichetta' => $giorno->locale($staff->lingua)->translatedFormat('D j'),
                'camere' => array_map(fn ($c) => ['numero' => $c['numero'], 'tipo' => $c['tipo']], $camere),
            ];
        }

        return $risultato;
    }

    /** L'ultima titolare della camera prima di quella data. */
    private function titolare($storico, string $data): ?int
    {
        $ultima = null;
        foreach ($storico ?? [] as $g) {
            if ($g->data->toDateString() >= $data) {
                break;
            }
            $ultima = $g->staff_id;
        }

        return $ultima;
    }
}
