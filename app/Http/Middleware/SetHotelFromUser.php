<?php

namespace App\Http\Middleware;

use App\Support\CurrentHotel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Imposta l'hotel attivo in base all'utente della reception loggato. */
class SetHotelFromUser
{
    public function __construct(private CurrentHotel $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $hotel = $user?->hotel;

        abort_if($hotel === null, 403, 'Nessun hotel associato a questo utente.');
        abort_unless($hotel->attivo, 403, 'Hotel non attivo.');

        $this->current->set($hotel);

        return $next($request);
    }
}
