<?php

use App\Http\Middleware\AuthenticateStaff;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetHotelFromUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'hotel' => SetHotelFromUser::class,
            'ruolo' => EnsureRole::class,
            'addetta' => AuthenticateStaff::class,
        ]);
        // L'hotel va impostato PRIMA del route model binding, altrimenti
        // /reception/foto/{issue} troverebbe anche le segnalazioni di altri hotel.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetHotelFromUser::class);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: AuthenticateStaff::class);
        // Hostinger sta dietro un proxy: serve per riconoscere HTTPS e l'IP reale.
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(fn (Request $r) => $r->user()?->isSuperadmin() ? '/admin' : '/reception');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
