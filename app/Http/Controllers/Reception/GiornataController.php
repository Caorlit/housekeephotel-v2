<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Services\GeneratoreGiornate;
use App\Support\CurrentHotel;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Preparazione del giorno: quali camere si puliscono, di che tipo, e a chi vanno.
 * La pagina lavora "a pennelli" (si sceglie uno strumento e si toccano le camere):
 * ogni tocco chiama applica() e riceve lo stato aggiornato.
 */
class GiornataController extends Controller
{
    public function index(Request $request, CurrentHotel $hotel, GeneratoreGiornate $generatore): View
    {
        $data = $this->dataValida($request->query('data'), $hotel);
        if ($data >= $hotel->get()->oggi()) {
            $generatore->genera($hotel->get(), $data);
        }

        return view('reception.giornata', [
            'iniziale' => $this->stato($data),
            'oggi' => $hotel->get()->oggi(),
        ]);
    }

    /** Pennello applicato a una o più camere. */
    public function applica(Request $request, string $data): JsonResponse
    {
        $this->verificaData($data);
        $dati = $request->validate([
            'room_ids' => ['required', 'array', 'max:500'],
            'room_ids.*' => ['integer'],
            'azione' => ['required', Rule::in(['assegna', 'tipo', 'togli'])],
            'staff_id' => ['nullable', 'integer'],
            'tipo' => ['nullable', Rule::in(RoomDay::TIPI)],
        ]);
        $staff = $this->addettaValida($dati['staff_id'] ?? null);
        $tipo = $dati['tipo'] ?? 'partenza';
        $avvisi = [];

        foreach (Room::whereIn('id', $dati['room_ids'])->get() as $room) {
            if ($dati['azione'] === 'assegna') {
                $this->giorno($room, $data, $tipo)->update(['staff_id' => $staff?->id, 'sostituzione' => false]);
            } elseif ($dati['azione'] === 'tipo') {
                $this->giorno($room, $data, $tipo)->update(['tipo' => $tipo]);
            } elseif (! $this->togli($room, $data)) {
                $avvisi[] = "La camera {$room->numero} è già iniziata o ha una segnalazione: non si può togliere.";
            }
        }

        $this->registra($request, 'giornata_'.$dati['azione'], $data, $dati);

        return response()->json($this->stato($data) + ['avvisi' => $avvisi]);
    }

    /** Inserimento rapido: numeri di camera incollati (virgole, spazi o a capo). */
    public function incolla(Request $request, string $data): JsonResponse
    {
        $this->verificaData($data);
        $dati = $request->validate([
            'testo' => ['required', 'string', 'max:5000'],
            'tipo' => ['required', Rule::in(RoomDay::TIPI)],
            'staff_id' => ['nullable', 'integer'],
        ]);
        $staff = $this->addettaValida($dati['staff_id'] ?? null);

        $numeri = collect(preg_split('/[\s,;]+/u', $dati['testo']))->map(fn ($n) => trim($n))->filter()->unique()->values();
        $camere = Room::whereIn('numero', $numeri)->get()->keyBy('numero');

        foreach ($numeri as $n) {
            if ($room = $camere->get($n)) {
                $giorno = $this->giorno($room, $data, $dati['tipo']);
                $giorno->update(['tipo' => $dati['tipo']] + ($staff ? ['staff_id' => $staff->id] : []));
            }
        }

        $sconosciute = $numeri->reject(fn ($n) => $camere->has($n))->values();
        $avvisi = $sconosciute->isEmpty() ? [] : ['Camere non trovate (aggiungile nella pagina Camere): '.$sconosciute->implode(', ')];

        $this->registra($request, 'giornata_incolla', $data, ['numeri' => $numeri->all(), 'tipo' => $dati['tipo']]);

        return response()->json($this->stato($data) + [
            'avvisi' => $avvisi,
            'messaggio' => ($numeri->count() - $sconosciute->count()).' camere inserite.',
        ]);
    }

    /**
     * Distribuzione automatica delle camere NON ancora assegnate, in modo equo
     * (tenendo conto di quelle che ognuna ha già) e a blocchi per piano.
     */
    public function distribuisci(Request $request, string $data): JsonResponse
    {
        $this->verificaData($data);
        $dati = $request->validate([
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['integer'],
        ]);
        $addette = Staff::where('attivo', true)->whereIn('id', $dati['staff_ids'])->orderBy('nome')->get();
        abort_if($addette->isEmpty(), 422, 'Nessuna addetta valida.');

        $giorni = RoomDay::delGiorno($data)->with('room')->get();
        $libere = $giorni->whereNull('staff_id')
            ->sortBy(fn ($g) => sprintf('%05d|%05d|%s', $g->room->piano + 100, strlen($g->room->numero), $g->room->numero))
            ->values();

        $quote = $this->quote($addette, $giorni, $libere->count());
        $i = 0;
        foreach ($addette as $a) {
            foreach ($libere->slice($i, $quote[$a->id])->all() as $g) {
                $g->update(['staff_id' => $a->id]);
            }
            $i += $quote[$a->id];
        }

        $this->registra($request, 'giornata_distribuisci', $data, ['staff_ids' => $addette->pluck('id')->all(), 'camere' => $libere->count()]);

        return response()->json($this->stato($data) + [
            'avvisi' => [],
            'messaggio' => $libere->isEmpty() ? 'Non ci sono camere da assegnare.' : "{$libere->count()} camere distribuite.",
        ]);
    }

    /**
     * Giorno libero: tutte le camere non ancora pronte di un'addetta passano a un'altra.
     * Sono segnate come sostituzione, così dal giorno dopo tornano alla titolare.
     */
    public function sostituisci(Request $request, string $data): JsonResponse
    {
        $this->verificaData($data);
        $dati = $request->validate([
            'da' => ['required', 'integer'],
            'a' => ['nullable', 'integer', 'different:da'],
        ]);
        $da = Staff::findOrFail($dati['da']);
        $a = $this->addettaValida($dati['a'] ?? null);

        $n = RoomDay::delGiorno($data)
            ->where('staff_id', $da->id)
            ->where('stato', '!=', RoomDay::PRONTA)
            ->update(['staff_id' => $a?->id, 'sostituzione' => $a !== null, 'updated_at' => now()]);

        $this->registra($request, 'giornata_sostituisci', $data, ['da' => $da->id, 'a' => $a?->id, 'camere' => $n]);

        return response()->json($this->stato($data) + [
            'avvisi' => [],
            'messaggio' => $n
                ? "{$n} camere di {$da->nome} passate a ".($a?->nome ?? 'nessuna').'.'
                : "{$da->nome} non ha camere da spostare.",
        ]);
    }

    /** Copia le camere (tipo e addetta) del giorno prima, senza toccare quelle già presenti. */
    public function copiaIeri(Request $request, string $data): JsonResponse
    {
        $this->verificaData($data);
        $ieri = CarbonImmutable::parse($data)->subDay()->toDateString();
        $attive = Staff::where('attivo', true)->pluck('id')->flip();
        $presenti = RoomDay::delGiorno($data)->pluck('room_id')->flip();
        $copiate = 0;

        foreach (RoomDay::delGiorno($ieri)->with('room')->get() as $g) {
            if ($presenti->has($g->room_id) || ! $g->room?->attiva) {
                continue;
            }
            RoomDay::create([
                'room_id' => $g->room_id,
                'data' => $data,
                'tipo' => $g->tipo,
                'staff_id' => $g->staff_id && $attive->has($g->staff_id) ? $g->staff_id : null,
            ]);
            $copiate++;
        }

        $this->registra($request, 'giornata_copia_ieri', $data, ['copiate' => $copiate]);

        return response()->json($this->stato($data) + [
            'avvisi' => [],
            'messaggio' => $copiate ? "Copiate {$copiate} camere da ieri." : 'Niente da copiare da ieri.',
        ]);
    }

    // --- Supporto --------------------------------------------------------------

    /** Quante camere libere dare a ciascuna, perché alla fine ne abbiano un numero simile. */
    private function quote(Collection $addette, Collection $giorni, int $libere): array
    {
        $attuali = $addette->mapWithKeys(fn ($a) => [$a->id => $giorni->where('staff_id', $a->id)->count()]);
        $k = $addette->count();
        $totale = $attuali->sum() + $libere;

        $quote = [];
        foreach ($addette->values() as $i => $a) {
            $obiettivo = intdiv($totale, $k) + ($i < $totale % $k ? 1 : 0);
            $quote[$a->id] = max(0, $obiettivo - $attuali[$a->id]);
        }
        // Se qualcuna aveva già più del dovuto, le altre quote superano le camere libere: si riducono.
        while (array_sum($quote) > $libere) {
            $quote[array_search(max($quote), $quote)]--;
        }

        return $quote;
    }

    private function giorno(Room $room, string $data, string $tipo): RoomDay
    {
        return RoomDay::delGiorno($data)->where('room_id', $room->id)->first()
            ?? RoomDay::create(['room_id' => $room->id, 'data' => $data, 'tipo' => $tipo]);
    }

    private function togli(Room $room, string $data): bool
    {
        $giorno = RoomDay::delGiorno($data)->where('room_id', $room->id)->withCount('issues')->first();
        if (! $giorno) {
            return true;
        }
        if ($giorno->stato !== RoomDay::DA_FARE || $giorno->issues_count > 0) {
            return false;
        }
        $giorno->delete();

        return true;
    }

    private function addettaValida(?int $id): ?Staff
    {
        if ($id === null) {
            return null;
        }
        $staff = Staff::where('attivo', true)->find($id);
        abort_unless($staff, 422, 'Addetta non valida.');

        return $staff;
    }

    private function verificaData(string $data): void
    {
        [$a, $m, $g] = array_map('intval', explode('-', $data));
        abort_unless(checkdate($m, $g, $a), 404);
    }

    private function dataValida(?string $data, CurrentHotel $hotel): string
    {
        return $data && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && strtotime($data) ? $data : $hotel->get()->oggi();
    }

    private function registra(Request $request, string $tipo, string $data, array $payload): void
    {
        EventLog::registra($tipo, ['user_id' => $request->user()->id], ['data' => $data] + $payload);
    }

    /** Tutto quello che serve alla pagina. */
    public function stato(string $data): array
    {
        $giorni = RoomDay::delGiorno($data)->get()->keyBy('room_id');

        $camere = Room::query()
            ->where(fn ($q) => $q->where('attiva', true)->orWhereIn('id', $giorni->keys()))
            ->orderBy('piano')->orderByRaw('LENGTH(numero), numero')
            ->get()
            ->map(fn (Room $r) => [
                'id' => $r->id,
                'numero' => $r->numero,
                'piano' => $r->piano,
                'giorno' => ($g = $giorni->get($r->id)) ? [
                    'tipo' => $g->tipo,
                    'stato' => $g->stato,
                    'staff_id' => $g->staff_id,
                    'urgente' => $g->urgente,
                ] : null,
            ])->values();

        $addette = Staff::where('attivo', true)->orderBy('nome')->get()->values()
            ->map(fn (Staff $s, int $i) => [
                'id' => $s->id,
                'nome' => $s->nome,
                'colore' => Staff::COLORI[$i % count(Staff::COLORI)],
            ]);

        return ['data' => $data, 'camere' => $camere, 'addette' => $addette];
    }
}
