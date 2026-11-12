<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'indirizzo', 'telefono', 'email', 'pin_richiesto', 'fuso_orario', 'attivo'];

    protected function casts(): array
    {
        return [
            'pin_richiesto' => 'boolean',
            'attivo' => 'boolean',
        ];
    }

    /** Adesso, nel fuso orario dell'hotel. */
    public function adesso(): CarbonImmutable
    {
        return CarbonImmutable::now($this->fuso_orario ?: 'Europe/Rome');
    }

    /** La data di "oggi" per l'hotel (Y-m-d). */
    public function oggi(): string
    {
        return $this->adesso()->toDateString();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
