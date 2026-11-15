<?php

namespace App\Http\Controllers;

use App\Models\TrialRequest;
use App\Support\Avvisi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Modulo "Prova gratis" del sito housekeephotel.com (pagina statica su un altro dominio):
 * è un normale form HTML che arriva qui e torna al sito (pagina "grazie").
 * Anti-spam: campo nascosto "sito_web" (i robot lo compilano) + limite di invii per IP.
 */
class RichiestaProvaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $lingua = in_array($request->input('lingua'), ['it', 'en', 'es'], true) ? $request->input('lingua') : 'it';
        $sito = rtrim(config('housekeep.sito_url'), '/').($lingua === 'it' ? '' : '/'.$lingua);

        // Robot: fingiamo che sia andato tutto bene.
        if (filled($request->input('sito_web'))) {
            return redirect()->away($sito.'/grazie/');
        }

        $v = Validator::make($request->all(), [
            'nome' => ['required', 'string', 'max:100'],
            'hotel' => ['required', 'string', 'max:150'],
            'citta' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'camere' => ['nullable', 'integer', 'between:1,5000'],
            'messaggio' => ['nullable', 'string', 'max:2000'],
            'privacy' => ['accepted'],
        ]);
        if ($v->fails()) {
            return redirect()->away($sito.'/?errore=1#prova');
        }

        $r = TrialRequest::create($v->validated() + ['lingua' => $lingua]);

        Avvisi::superadmin("richiesta di prova: {$r->hotel}", array_filter([
            "{$r->nome} chiede la prova gratuita per {$r->hotel}".($r->citta ? " ({$r->citta})" : '').'.',
            'Email: '.$r->email.($r->telefono ? ' · Telefono: '.$r->telefono : ''),
            $r->camere ? "Camere: {$r->camere}" : null,
            $r->messaggio ? 'Messaggio: '.$r->messaggio : null,
            'Lingua del sito: '.strtoupper($lingua),
        ]), route('admin.richieste'), 'Apri le richieste');

        return redirect()->away($sito.'/grazie/');
    }
}
