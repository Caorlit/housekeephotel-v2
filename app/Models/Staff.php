<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Un'addetta alle pulizie. */
class Staff extends Model
{
    use BelongsToHotel, HasFactory;

    public const LINGUE = [
        'it' => 'Italiano',
        'en' => 'English',
        'ro' => 'Română',
        'uk' => 'Українська',
        'es' => 'Español',
        'bn' => 'বাংলা',
    ];

    /** Colori per distinguere le addette nella preparazione del giorno (non usano grigio/giallo/verde/rosso). */
    public const COLORI = ['#2563eb', '#9333ea', '#ea580c', '#0d9488', '#db2777', '#4f46e5', '#a16207', '#0891b2', '#65a30d', '#be123c'];

    protected $table = 'staff';

    protected $fillable = ['hotel_id', 'nome', 'lingua', 'attivo'];

    protected $hidden = ['token_accesso', 'pin'];

    protected function casts(): array
    {
        return ['attivo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Staff $staff) {
            $staff->token_accesso ??= self::nuovoToken();
        });
    }

    public static function nuovoToken(): string
    {
        return Str::random(64);
    }

    /** Invalida il vecchio link/QR e ne crea uno nuovo. */
    public function rigeneraToken(): void
    {
        $this->token_accesso = self::nuovoToken();
        $this->save();
    }

    public function impostaPin(?string $pin): void
    {
        $this->pin = $pin === null || $pin === '' ? null : Hash::make($pin);
        $this->save();
    }

    public function verificaPin(string $pin): bool
    {
        return $this->pin !== null && Hash::check($pin, $this->pin);
    }

    /** Il PIN serve solo se l'hotel lo richiede e l'addetta ne ha uno. */
    public function pinNecessario(): bool
    {
        return $this->hotel->pin_richiesto && $this->pin !== null;
    }

    public function linkPersonale(): string
    {
        return route('addetta.home', $this->token_accesso);
    }

    public function roomDays(): HasMany
    {
        return $this->hasMany(RoomDay::class);
    }
}
