<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso: ->middleware('ruolo:superadmin') */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$ruoli): Response
    {
        $user = $request->user();
        if ($user && ! $user->attivo) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect('/login')->withErrors(['email' => 'Il tuo accesso è stato disattivato.']);
        }
        abort_unless($user && in_array($user->ruolo, $ruoli, true), 403);

        return $next($request);
    }
}
