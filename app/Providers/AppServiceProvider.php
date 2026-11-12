<?php

namespace App\Providers;

use App\Support\CurrentHotel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Un solo "hotel attivo" per richiesta.
        $this->app->scoped(CurrentHotel::class);
    }

    public function boot(): void
    {
        // Endpoint pubblici delle addette: per token e per IP.
        RateLimiter::for('addetta', fn (Request $request) => [
            Limit::perMinute(120)->by('t:'.substr((string) $request->route('token'), 0, 16)),
            Limit::perMinute(300)->by('ip:'.$request->ip()),
        ]);
    }
}
