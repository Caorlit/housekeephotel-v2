<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\Room;
use App\Models\Stay;
use App\Services\GeneratoreGiornate;
use App\Support\CurrentHotel;
use App\Support\LettoreCsvPrenotazioni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Import delle prenotazioni da CSV in due passi: anteprima, poi conferma.
 * Reimportare lo stesso file non crea doppioni (chiave: codice + camera).
 */
class ImportaPrenotazioniController extends Controller
{
    private const SESSIONE = 'import_prenotazioni';

    public function form(): View
    {
        return view('reception.prenotazioni-importa', ['frequenze' => Stay::FREQUENZE]);
    }

    public function anteprima(Request $request, LettoreCsvPrenotazioni $lettore, CurrentHotel $hotel): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
            'riassetto_ogni' => ['required', 'integer', Rule::in(array_keys(Stay::FREQUENZE))],
        ], ['file.mimes' => 'Il file deve essere un CSV (in Excel: File → Salva con nome → CSV).']);

        $letto = $lettore->leggi($request->file('file')->get());
        if ($letto['errore']) {
            return back()->withErrors(['file' => $letto['errore']]);
        }

        $righe = $this->valuta($letto['righe']);
        $request->session()->put(self::SESSIONE, [
            'hotel_id' => $hotel->id(),
            'riassetto_ogni' => (int) $request->input('riassetto_ogni'),
            'righe' => array_values(array_filter($righe, fn ($r) => in_array($r['esito'], ['nuova', 'aggiornata'], true))),
        ]);

        return view('reception.prenotazioni-anteprima', [
            'righe' => $righe,
            'conteggi' => collect($righe)->countBy('esito')->all(),
            'riassetto' => Stay::FREQUENZE[(int) $request->input('riassetto_ogni')],
        ]);
    }

    public function conferma(Request $request, GeneratoreGiornate $generatore, CurrentHotel $hotel): RedirectResponse
    {
        $import = $request->session()->pull(self::SESSIONE);
        if (! $import || $import['hotel_id'] !== $hotel->id()) {
            return redirect()->route('reception.prenotazioni.importa')->withErrors(['file' => 'Anteprima scaduta: ricarica il file.']);
        }

        $toccate = [];
        $nuove = 0;
        $aggiornate = 0;

        DB::transaction(function () use ($import, &$toccate, &$nuove, &$aggiornate) {
            foreach ($import['righe'] as $r) {
                // Ricontrolla: nel frattempo qualcuno potrebbe aver inserito una prenotazione.
                $esistente = $r['stay_id'] ? Stay::find($r['stay_id']) : null;
                if (Stay::sovrapposti($r['room_id'], $r['arrivo'], $r['partenza'], $esistente?->id)->exists()) {
                    continue;
                }
                if ($esistente) {
                    $toccate[] = [$esistente->room_id, $esistente->arrivo->toDateString(), $esistente->partenza->toDateString()];
                    $esistente->update(['arrivo' => $r['arrivo'], 'partenza' => $r['partenza'], 'pax' => $r['pax']]);
                    $aggiornate++;
                } else {
                    Stay::create([
                        'room_id' => $r['room_id'], 'arrivo' => $r['arrivo'], 'partenza' => $r['partenza'],
                        'riferimento' => $r['riferimento'], 'pax' => $r['pax'],
                        'riassetto_ogni' => $import['riassetto_ogni'], 'fonte' => 'csv',
                    ]);
                    $nuove++;
                }
                $toccate[] = [$r['room_id'], $r['arrivo'], $r['partenza']];
            }
        });

        foreach ($toccate as [$roomId, $dal, $al]) {
            $generatore->sincronizza($hotel->get(), $roomId, $dal, $al);
        }

        EventLog::registra('prenotazioni_importate', ['user_id' => $request->user()->id], ['nuove' => $nuove, 'aggiornate' => $aggiornate]);

        return redirect()->route('reception.prenotazioni')->with('ok', "Import completato: {$nuove} nuove, {$aggiornate} aggiornate.");
    }

    /** Per ogni riga: nuova, aggiornata, invariata o scartata (con il motivo). */
    private function valuta(array $righe): array
    {
        $camere = Room::whereIn('numero', array_column($righe, 'camera'))->get()->keyBy('numero');
        $nelFile = []; // room_id => [[arrivo, partenza, riga]]
        $chiavi = [];

        foreach ($righe as &$r) {
            $r['esito'] = 'scartata';
            $r['room_id'] = null;
            $r['stay_id'] = null;

            if ($r['errori']) {
                continue;
            }
            $room = $camere->get($r['camera']);
            if (! $room) {
                $r['errori'][] = "camera {$r['camera']} non trovata";

                continue;
            }
            $r['room_id'] = $room->id;

            $chiave = $room->id.'|'.($r['riferimento'] ?? $r['arrivo'].'|'.$r['partenza']);
            if (isset($chiavi[$chiave])) {
                $r['errori'][] = "ripetuta (riga {$chiavi[$chiave]})";

                continue;
            }
            $chiavi[$chiave] = $r['riga'];

            foreach ($nelFile[$room->id] ?? [] as [$a, $p, $riga]) {
                if ($r['arrivo'] < $p && $r['partenza'] > $a) {
                    $r['errori'][] = "si sovrappone alla riga {$riga}";

                    continue 2;
                }
            }

            $esistente = $r['riferimento']
                ? Stay::where('room_id', $room->id)->where('riferimento', $r['riferimento'])->first()
                : Stay::where('room_id', $room->id)->whereDate('arrivo', $r['arrivo'])->whereDate('partenza', $r['partenza'])->first();

            $conflitto = Stay::sovrapposti($room->id, $r['arrivo'], $r['partenza'], $esistente?->id)->first();
            if ($conflitto) {
                $r['errori'][] = sprintf('camera già occupata dal %s al %s', $conflitto->arrivo->format('d/m'), $conflitto->partenza->format('d/m'));

                continue;
            }

            $nelFile[$room->id][] = [$r['arrivo'], $r['partenza'], $r['riga']];
            $r['stay_id'] = $esistente?->id;
            $r['esito'] = match (true) {
                $esistente === null => 'nuova',
                $esistente->arrivo->toDateString() === $r['arrivo'] && $esistente->partenza->toDateString() === $r['partenza'] && $esistente->pax === $r['pax'] => 'invariata',
                default => 'aggiornata',
            };
        }

        return $righe;
    }
}
