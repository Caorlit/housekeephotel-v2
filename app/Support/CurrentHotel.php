<?php

namespace App\Support;

use App\Models\Hotel;

/**
 * L'hotel "attivo" della richiesta corrente.
 *
 * Viene impostato dal middleware (utente reception o token dell'addetta).
 * Tutti i modelli con BelongsToHotel filtrano automaticamente su questo id.
 */
class CurrentHotel
{
    private ?Hotel $hotel = null;

    public function set(?Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function get(): ?Hotel
    {
        return $this->hotel;
    }

    public function id(): ?int
    {
        return $this->hotel?->id;
    }

    public function has(): bool
    {
        return $this->hotel !== null;
    }
}
