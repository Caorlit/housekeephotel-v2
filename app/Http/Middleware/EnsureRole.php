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
        abort_unless($request->user() && in_array($request->user()->ruolo, $ruoli, true), 403);

        return $next($request);
    }
}
