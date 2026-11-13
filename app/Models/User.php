<?php

namespace App\Models;

use App\Notifications\InvitoUtente;
use App\Notifications\ReimpostaPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;

/**
 * Utenti della reception e superadmin (login con email e password).
 * Non usa il filtro automatico per hotel: il superadmin non ha hotel.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const RUOLO_SUPERADMIN = 'superadmin';

    public const RUOLO_RECEPTION = 'reception';

    protected $fillable = ['hotel_id', 'nome', 'email', 'password', 'ruolo', 'attivo'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['attivo' => true, 'ruolo' => self::RUOLO_RECEPTION];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'attivo' => 'boolean',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /** Email "password dimenticata" in italiano. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ReimpostaPassword($token));
    }

    /** Manda (o rimanda) l'invito: link per scegliere la password, valido 7 giorni. */
    public function inviaInvito(): void
    {
        $token = Password::broker('inviti')->createToken($this);
        $this->notify(new InvitoUtente($token));
    }

    public function isSuperadmin(): bool
    {
        return $this->ruolo === self::RUOLO_SUPERADMIN;
    }
}
