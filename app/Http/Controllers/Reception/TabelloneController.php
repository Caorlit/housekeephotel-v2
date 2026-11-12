<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\Hotel;
use App\Models\Issue;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Services\RoomStateService;
use App\Support\CurrentHotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Tabellone della reception: tutte le camere di oggi, aggiornate ogni 5 secondi.
 * dati() risponde 204 se dall'ultima volta non è cambiato niente.
 */
class TabelloneController extends Controller
{
    public function index(CurrentHotel $hotel): View
    {
        return view('reception.tabellone', ['dati' => $this->raccogli($hotel->get())]);
    }

    public function dati(Request $request, CurrentHotel $hotel): JsonResponse|Response
    {
        $dati = $this->raccogli($hotel->get());

        if ($request->query('versione') === $dati['versione']) {
            return response()->noContent();
        }

        return response()->json($dati);
    }

    /** La reception cambia a mano lo stato di una camera. */
    public function stato(Request $request, RoomDay $roomDay, RoomStateService $servizio, CurrentHotel $hotel): JsonResponse
    {
        $dati = $request->validate(['stato' => ['required', Rule::in(RoomDay::STATI)]]);

        $servizio->cambiaStato($roomDay, $dati['stato'], $request->user());

        return response()->json($this->raccogli($hotel->get()));
    }

    /** Urgente, nota per l'addetta, riassegnazione. */
    public function aggiorna(Request $request, RoomDay $roomDay, CurrentHotel $hotel): JsonResponse
    {
        $dati = $request->validate([
            'urgente' => ['sometimes', 'boolean'],
            'nota_reception' => ['sometimes', 'nullable', 'string', 'max:500'],
            'staff_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        if (array_key_exists('staff_id', $dati) && $dati['staff_id'] !== null) {
            abort_unless(Staff::where('attivo', true)->whereKey($dati['staff_id'])->exists(), 422, 'Addetta non valida.');
        }

        $prima = $roomDay->only(array_keys($dati));
        $roomDay->update($dati);
        EventLog::registra('camera_modificata', [
            'room_day_id' => $roomDay->id,
            'user_id' => $request->user()->id,
        ], ['prima' => $prima, 'dopo' => $dati]);

        return response()->json($this->raccogli($hotel->get()));
    }

    /** Segnalazione: presa in carico o risolta. */
    public function segnalazione(Request $request, Issue $issue, CurrentHotel $hotel): JsonResponse
    {
        $dati = $request->validate(['stato' => ['required', Rule::in([Issue::PRESA_IN_CARICO, Issue::RISOLTA, Issue::APERTA])]]);

        $issue->stato = $dati['stato'];
        $issue->letta_alle ??= now();
        $issue->risolta_alle = $dati['stato'] === Issue::RISOLTA ? now() : null;
        $issue->save();

        EventLog::registra('segnalazione_'.$dati['stato'], [
            'room_day_id' => $issue->room_day_id,
            'user_id' => $request->user()->id,
        ], ['issue_id' => $issue->id]);

        return response()->json($this->raccogli($hotel->get()));
    }

    private function raccogli(Hotel $hotel): array
    {
        $oggi = $hotel->oggi();
        $fuso = $hotel->fuso_orario;
        $ora = fn ($t) => $t?->timezone($fuso)->format('H:i');

        $camere = RoomDay::delGiorno($oggi)
            ->perNumero()
            ->with(['room', 'staff'])
            ->withCount(['issues as segnalazioni' => fn ($q) => $q->where('stato', '!=', Issue::RISOLTA)])
            ->get()
            ->map(fn (RoomDay $g) => [
                'id' => $g->id,
                'numero' => $g->room->numero,
                'piano' => $g->room->piano,
                'tipo' => $g->tipo,
                'stato' => $g->stato,
                'urgente' => $g->urgente,
                'nota' => $g->nota_reception,
                'note_fisse' => $g->room->note_fisse,
                'staff_id' => $g->staff_id,
                'staff' => $g->staff?->nome,
                'iniziata' => $ora($g->iniziata_alle),
                'finita' => $ora($g->finita_alle),
                'segnalazioni' => $g->segnalazioni,
            ])->values();

        $segnalazioni = Issue::where('stato', '!=', Issue::RISOLTA)
            ->with(['roomDay.room', 'staff'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Issue $i) => [
                'id' => $i->id,
                'room_day_id' => $i->room_day_id,
                'numero' => $i->roomDay?->room?->numero,
                'staff' => $i->staff?->nome,
                'nota' => $i->nota,
                'stato' => $i->stato,
                'quando' => $i->created_at->timezone($fuso)->isSameDay($hotel->adesso())
                    ? $i->created_at->timezone($fuso)->format('H:i')
                    : $i->created_at->timezone($fuso)->format('d/m H:i'),
                'foto' => $i->foto_path ? route('reception.foto', $i) : null,
            ])->values();

        $addette = Staff::where('attivo', true)->orderBy('nome')->get(['id', 'nome'])
            ->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome])->values();

        $dati = compact('oggi', 'camere', 'segnalazioni', 'addette');

        return $dati + ['versione' => md5(json_encode($dati))];
    }
}
