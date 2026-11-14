<?php

namespace App\Http\Controllers\Addetta;

use App\Events\IssueCreated;
use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\Issue;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Services\GeneratoreGiornate;
use App\Services\Previsione;
use App\Services\RoomStateService;
use App\Support\StaffPinCookie;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * La schermata unica delle addette: /s/{token}.
 * Niente sessione e niente CSRF: l'accesso è il token (lungo e segreto) nell'URL,
 * così le azioni salvate offline partono anche dopo ore.
 */
class AddettaController extends Controller
{
    private function staff(Request $request): Staff
    {
        return $request->attributes->get('staff');
    }

    public function home(Request $request): Response
    {
        $staff = $this->staff($request);
        $hotel = $staff->hotel;

        return response()->view('addetta.home', [
            'staff' => $staff,
            'oggi' => $hotel->adesso()->locale($staff->lingua),
            'dati' => $this->statoAttuale($staff),
            'testi' => trans('addetta'),
        ])->header('Cache-Control', 'no-store');
    }

    /** Polling: le camere di oggi (204 se nulla è cambiato). */
    public function camere(Request $request): JsonResponse|Response
    {
        $dati = $this->statoAttuale($this->staff($request));

        if ($request->query('versione') === $dati['versione']) {
            return response()->noContent();
        }

        return response()->json($dati);
    }

    /** Riceve un blocco di azioni (anche salvate offline) e le applica. */
    public function azioni(Request $request, RoomStateService $servizio): JsonResponse
    {
        $staff = $this->staff($request);

        $dati = $request->validate([
            'azioni' => ['required', 'array', 'max:100'],
            'azioni.*.client_action_id' => ['required', 'uuid'],
            'azioni.*.room_day_id' => ['required', 'integer'],
            'azioni.*.stato' => ['required', Rule::in(RoomDay::STATI)],
            'azioni.*.ts' => ['required', 'integer'],
        ]);

        $risultati = [];
        foreach ($dati['azioni'] as $azione) {
            // Il filtro per hotel è automatico: una camera di un altro hotel non si trova.
            $roomDay = RoomDay::find($azione['room_day_id']);
            if (! $roomDay) {
                $risultati[$azione['client_action_id']] = 'non_trovata';

                continue;
            }

            $quando = CarbonImmutable::createFromTimestampMs($azione['ts']);
            $risultati[$azione['client_action_id']] = $servizio->cambiaStato(
                $roomDay, $azione['stato'], $staff, $quando, $azione['client_action_id'],
            );
        }

        return response()->json(['risultati' => $risultati] + $this->statoAttuale($staff));
    }

    /** Pulsante rosso: segnalazione con foto (già compressa nel browser) e nota. */
    public function segnalazione(Request $request): JsonResponse
    {
        $staff = $this->staff($request);

        $dati = $request->validate([
            'client_action_id' => ['required', 'uuid'],
            'room_day_id' => ['required', 'integer'],
            'nota' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'ts' => ['nullable', 'integer'],
        ]);

        if (! $request->hasFile('foto') && blank($dati['nota'] ?? null)) {
            return response()->json(['errore' => 'foto_o_nota'], 422);
        }

        $esistente = Issue::withoutGlobalScopes()->where('client_action_id', $dati['client_action_id'])->first();
        if ($esistente) {
            return response()->json(['esito' => 'duplicata', 'id' => $esistente->id]);
        }

        $roomDay = RoomDay::findOrFail($dati['room_day_id']);
        $hotel = $staff->hotel;

        $path = null;
        if ($request->hasFile('foto')) {
            $cartella = 'segnalazioni/'.$hotel->id.'/'.now()->format('Y-m');
            $estensione = $request->file('foto')->guessExtension() ?: 'jpg';
            $path = $request->file('foto')->storeAs($cartella, Str::uuid().'.'.$estensione, 'local');
        }

        $issue = Issue::create([
            'room_day_id' => $roomDay->id,
            'staff_id' => $staff->id,
            'nota' => $dati['nota'] ?? null,
            'foto_path' => $path,
            'stato' => Issue::APERTA,
            'client_action_id' => $dati['client_action_id'],
        ]);

        EventLog::registra('segnalazione_creata', [
            'room_day_id' => $roomDay->id,
            'staff_id' => $staff->id,
        ], ['issue_id' => $issue->id, 'con_foto' => $path !== null]);

        IssueCreated::dispatch($issue);

        return response()->json(['esito' => 'applicata', 'id' => $issue->id], 201);
    }

    public function verificaPin(Request $request)
    {
        $staff = $this->staff($request);
        $chiave = 'pin:'.$staff->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($chiave, 5)) {
            return response()->view('addetta.pin', ['staff' => $staff, 'errore' => true, 'attendi' => RateLimiter::availableIn($chiave)], 429);
        }

        $pin = preg_replace('/\D/', '', (string) $request->input('pin'));
        if (! $staff->verificaPin($pin)) {
            RateLimiter::hit($chiave, 300);

            return response()->view('addetta.pin', ['staff' => $staff, 'errore' => true], 422);
        }

        RateLimiter::clear($chiave);

        return redirect()->route('addetta.home', $staff->token_accesso)->withCookie(StaffPinCookie::crea($staff));
    }

    /** Manifest PWA personale: l'icona sulla home apre direttamente la schermata dell'addetta. */
    public function manifest(Request $request): JsonResponse
    {
        $staff = $this->staff($request);

        return response()->json([
            'name' => 'Housekeep – '.$staff->nome,
            'short_name' => 'Housekeep',
            'start_url' => route('addetta.home', $staff->token_accesso, false),
            'scope' => '/s/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#f8fafc',
            'theme_color' => '#0f172a',
            'lang' => $staff->lingua,
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    /** Le camere di oggi dell'addetta, nel formato usato dal browser. */
    private function statoAttuale(Staff $staff): array
    {
        $oggi = $staff->hotel->oggi();
        app(GeneratoreGiornate::class)->genera($staff->hotel, $oggi);

        $camere = RoomDay::query()
            ->delGiorno($oggi)
            ->where('room_days.staff_id', $staff->id)
            ->ordinate() // prima di withCount: imposta le colonne della select
            ->with('room')
            ->withCount(['issues as segnalazioni' => fn ($q) => $q->where('stato', '!=', Issue::RISOLTA)])
            ->get()
            ->map(fn (RoomDay $rd) => [
                'id' => $rd->id,
                'numero' => $rd->room->numero,
                'piano' => $rd->room->piano,
                'tipo' => $rd->tipo,
                'stato' => $rd->stato,
                'urgente' => $rd->urgente,
                'nota' => trim(implode("\n", array_filter([$rd->nota_reception, $rd->room->note_fisse]))) ?: null,
                'segnalazioni' => $rd->segnalazioni,
                'cambiata' => $rd->stato_cambiato_alle?->getTimestampMs(),
            ])
            ->values()
            ->all();

        $prossimi = app(Previsione::class)->perAddetta($staff);

        return [
            'data' => $oggi,
            'camere' => $camere,
            'prossimi' => $prossimi,
            'versione' => md5(json_encode([$camere, $prossimi])),
            'generato' => now()->getTimestampMs(),
        ];
    }
}
