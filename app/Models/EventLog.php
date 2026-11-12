<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/** Registro eventi (tabella "events"): storico e statistiche. */
class EventLog extends Model
{
    use BelongsToHotel;

    protected $table = 'events';

    public const UPDATED_AT = null;

    protected $fillable = ['hotel_id', 'room_day_id', 'staff_id', 'user_id', 'tipo', 'payload', 'client_action_id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public static function registra(string $tipo, array $attributi = [], array $payload = []): self
    {
        return static::create($attributi + ['tipo' => $tipo, 'payload' => $payload ?: null]);
    }
}
