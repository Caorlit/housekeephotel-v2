<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use App\Support\CurrentHotel;
use App\Support\StaffPinCookie;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accesso delle addette tramite il token personale nell'URL (/s/{token}).
 * Imposta l'hotel attivo e la lingua dell'addetta.
 * Se l'hotel richiede il PIN, serve anche il cookie "dispositivo ricordato".
 */
class AuthenticateStaff
{
    public function __construct(private CurrentHotel $current) {}

    public function handle(Request $request, Closure $next, string $controllaPin = 'si'): Response
    {
        $token = (string) $request->route('token');

        $staff = strlen($token) === 64
            ? Staff::withoutGlobalScopes()->with('hotel')->where('token_accesso', $token)->first()
            : null;

        if (! $staff || ! hash_equals($staff->token_accesso, $token) || ! $staff->attivo || ! $staff->hotel?->attivo) {
            return $request->expectsJson()
                ? response()->json(['errore' => 'link_non_valido'], 404)
                : response()->view('addetta.link-non-valido', [], 404);
        }

        $this->current->set($staff->hotel);
        app()->setLocale($staff->lingua);
        $request->attributes->set('staff', $staff);

        if ($controllaPin === 'si' && $staff->pinNecessario() && ! StaffPinCookie::valido($request, $staff)) {
            return $request->expectsJson()
                ? response()->json(['errore' => 'pin_richiesto'], 401)
                : response()->view('addetta.pin', ['staff' => $staff, 'errore' => false]);
        }

        return $next($request);
    }
}
