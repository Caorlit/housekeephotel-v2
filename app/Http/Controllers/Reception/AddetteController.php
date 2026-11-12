<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\Staff;
use App\Support\CurrentHotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Gestione delle addette: aggiunta, lingua, QR/link personale, PIN. */
class AddetteController extends Controller
{
    public function index(CurrentHotel $hotel): View
    {
        return view('reception.addette', [
            'addette' => Staff::orderByDesc('attivo')->orderBy('nome')->get(),
            'hotel' => $hotel->get(),
            'lingue' => Staff::LINGUE,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dati = $request->validate([
            'nome' => ['required', 'string', 'max:60'],
            'lingua' => ['required', Rule::in(array_keys(Staff::LINGUE))],
        ]);

        $staff = Staff::create($dati);
        EventLog::registra('addetta_creata', ['user_id' => $request->user()->id, 'staff_id' => $staff->id]);

        return back()->with('ok', "{$staff->nome} aggiunta. Ora stampa il suo QR code.");
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $dati = $request->validate([
            'nome' => ['required', 'string', 'max:60'],
            'lingua' => ['required', Rule::in(array_keys(Staff::LINGUE))],
            'attivo' => ['nullable', 'boolean'],
        ]);
        $dati['attivo'] = $request->boolean('attivo');

        $staff->update($dati);

        return back()->with('ok', "{$staff->nome} salvata.".($staff->attivo ? '' : ' Il suo link ora non funziona più.'));
    }

    /** Nuovo link/QR: quello vecchio smette subito di funzionare. */
    public function rigenera(Request $request, Staff $staff): RedirectResponse
    {
        $staff->rigeneraToken();
        EventLog::registra('link_rigenerato', ['user_id' => $request->user()->id, 'staff_id' => $staff->id]);

        return back()->with('ok', "Nuovo link per {$staff->nome}. Il vecchio non funziona più: stampa il nuovo QR code.");
    }

    public function pin(Request $request, Staff $staff): RedirectResponse
    {
        $dati = $request->validate(['pin' => ['nullable', 'digits:4']]);

        $staff->impostaPin($dati['pin'] ?? null);
        EventLog::registra($staff->pin ? 'pin_impostato' : 'pin_rimosso', ['user_id' => $request->user()->id, 'staff_id' => $staff->id]);

        return back()->with('ok', $staff->pin ? "PIN di {$staff->nome} impostato." : "PIN di {$staff->nome} tolto.");
    }

    public function pinHotel(Request $request, CurrentHotel $hotel): RedirectResponse
    {
        $hotel->get()->update(['pin_richiesto' => $request->boolean('pin_richiesto')]);

        return back()->with('ok', $hotel->get()->pin_richiesto
            ? 'PIN attivo: le addette con un PIN dovranno inserirlo (una volta ogni 90 giorni per telefono).'
            : 'PIN disattivato: basta il link o il QR code.');
    }

    /** Foglio A4 con QR grande e istruzioni con icone, nella lingua dell'addetta. */
    public function stampa(Staff $staff): View
    {
        return view('reception.addetta-stampa', [
            'staff' => $staff,
            'link' => $staff->linkPersonale(),
            'testi' => trans('addetta', [], $staff->lingua),
        ]);
    }
}
