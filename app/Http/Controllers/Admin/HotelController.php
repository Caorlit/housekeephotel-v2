<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\Hotel;
use App\Models\Issue;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\TrialRequest;
use App\Models\User;
use App\Support\Avvisi;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Superadmin: gli hotel. Qui non c'è un "hotel attivo": ogni query filtra a mano. */
class HotelController extends Controller
{
    public function index(): View
    {
        $hotels = Hotel::orderByDesc('attivo')->orderBy('nome')->get();
        $ids = $hotels->pluck('id');
        $conta = fn ($query) => $query->withoutGlobalScopes()->whereIn('hotel_id', $ids)->selectRaw('hotel_id, count(*) as n')->groupBy('hotel_id')->pluck('n', 'hotel_id');

        return view('admin.index', [
            'hotels' => $hotels,
            'camere' => $conta(Room::where('attiva', true)),
            'addette' => $conta(Staff::where('attivo', true)),
            'utenti' => User::whereIn('hotel_id', $ids)->selectRaw('hotel_id, count(*) as n')->groupBy('hotel_id')->pluck('n', 'hotel_id'),
            'pulite7' => $conta(RoomDay::where('stato', RoomDay::PRONTA)->where('finita_alle', '>=', now()->subDays(7))),
            'aperte' => $conta(Issue::where('stato', '!=', Issue::RISOLTA)),
            'ultima' => EventLog::withoutGlobalScopes()->whereIn('hotel_id', $ids)->selectRaw('hotel_id, max(created_at) as t')->groupBy('hotel_id')->pluck('t', 'hotel_id'),
        ]);
    }

    public function create(Request $request): View
    {
        // Dati precompilati quando si arriva da una richiesta di prova del sito.
        return view('admin.hotel-form', [
            'hotel' => new Hotel(['fuso_orario' => 'Europe/Rome', 'attivo' => true, 'prova_fino_al' => now()->addMonthsNoOverflow(Hotel::MESI_PROVA)->toDateString()]
                + $request->only(['nome', 'email', 'telefono', 'indirizzo'])),
            'fusi' => $this->fusi(),
            'richiesta' => $request->integer('richiesta') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hotel = Hotel::create($this->valida($request));
        if ($richiesta = TrialRequest::find($request->integer('richiesta'))) {
            $richiesta->update(['hotel_id' => $hotel->id, 'stato' => 'attivata']);
        }

        Avvisi::superadmin("nuovo hotel: {$hotel->nome}", [
            "È stato creato l'hotel {$hotel->nome}".($hotel->indirizzo ? " ({$hotel->indirizzo})" : '').' da '.$request->user()->nome.'.',
            'Prossimi passi: aggiungere un utente della reception, poi camere e addette.',
        ], route('admin.hotel.show', $hotel), 'Apri l\'hotel');

        return redirect()->route('admin.hotel.show', $hotel)->with('ok', "Hotel {$hotel->nome} creato. Ora aggiungi l'utente della reception.");
    }

    public function show(Hotel $hotel): View
    {
        return view('admin.hotel', [
            'hotel' => $hotel,
            'utenti' => User::where('hotel_id', $hotel->id)->orderByDesc('attivo')->orderBy('nome')->get(),
            'fusi' => $this->fusi(),
        ]);
    }

    public function update(Request $request, Hotel $hotel): RedirectResponse
    {
        $hotel->update($this->valida($request));

        return back()->with('ok', 'Hotel salvato.'.($hotel->attivo ? '' : ' È disattivato: reception e addette non possono entrare.'));
    }

    /** Pagamento annuale ricevuto: la scadenza va avanti di un anno (dalla scadenza attuale, o da oggi se già passata). */
    public function pagamento(Request $request, Hotel $hotel): RedirectResponse
    {
        $base = $hotel->scadenza() && $hotel->scadenza()->isFuture() ? $hotel->scadenza() : CarbonImmutable::today();
        $hotel->update(['pagato_fino_al' => $base->addYear()->toDateString()]);

        EventLog::withoutGlobalScopes()->create([
            'hotel_id' => $hotel->id, 'user_id' => $request->user()->id, 'tipo' => 'pagamento_registrato',
            'payload' => ['pagato_fino_al' => $hotel->pagato_fino_al->toDateString(), 'piano' => $hotel->piano],
        ]);

        return back()->with('ok', "Pagamento registrato: {$hotel->nome} è a posto fino al ".$hotel->pagato_fino_al->format('d/m/Y').'.');
    }

    /** Elimina l'hotel con tutti i suoi dati e foto. Va scritto il nome per conferma. */
    public function destroy(Request $request, Hotel $hotel): RedirectResponse
    {
        if (trim((string) $request->input('conferma')) !== $hotel->nome) {
            return back()->withErrors(['conferma' => 'Per eliminare scrivi esattamente il nome dell\'hotel: '.$hotel->nome]);
        }

        $nome = $hotel->nome;
        Storage::disk('local')->deleteDirectory('segnalazioni/'.$hotel->id);
        $hotel->delete(); // camere, addette, giorni, segnalazioni, prenotazioni, utenti: a cascata

        Avvisi::superadmin("hotel eliminato: {$nome}", ["L'hotel {$nome} e tutti i suoi dati sono stati eliminati da ".$request->user()->nome.'.']);

        return redirect()->route('admin.index')->with('ok', "Hotel {$nome} eliminato.");
    }

    private function valida(Request $request): array
    {
        $dati = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'indirizzo' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'fuso_orario' => ['required', Rule::in($this->fusi())],
            'pin_richiesto' => ['nullable', 'boolean'],
            'attivo' => ['nullable', 'boolean'],
            'piano' => ['nullable', Rule::in(array_keys(Hotel::PIANI))],
            'prova_fino_al' => ['nullable', 'date_format:Y-m-d'],
            'pagato_fino_al' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $dati['pin_richiesto'] = $request->boolean('pin_richiesto');
        $dati['attivo'] = $request->boolean('attivo');

        return $dati;
    }

    private function fusi(): array
    {
        return DateTimeZone::listIdentifiers(DateTimeZone::EUROPE);
    }
}
