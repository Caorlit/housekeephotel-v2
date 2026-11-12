<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Utenti della reception e superadmin (login con email e password).
 * Non usa il filtro automatico per hotel: il superadmin non ha hotel.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const RUOLO_SUPERADMIN = 'superadmin';

    public const RUOLO_RECEPTION = 'reception';

    protected $fillable = ['hotel_id', 'nome', 'email', 'password', 'ruolo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->ruolo === self::RUOLO_SUPERADMIN;
    }
}
