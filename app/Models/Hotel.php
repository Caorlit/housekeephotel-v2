<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;

    public const PIANI = ['small' => 'Small (fino a 35 camere)', 'medium' => 'Medium (36–70 camere)', 'large' => 'Large (oltre 70 camere)'];

    public const MESI_PROVA = 3;

    protected $fillable = ['nome', 'indirizzo', 'telefono', 'email', 'pin_richiesto', 'fuso_orario', 'attivo', 'piano', 'prova_fino_al', 'pagato_fino_al'];

    protected function casts(): array
    {
        return [
            'pin_richiesto' => 'boolean',
            'attivo' => 'boolean',
            'prova_fino_al' => 'date:Y-m-d',
            'pagato_fino_al' => 'date:Y-m-d',
        ];
    }

    /** La prossima scadenza: rinnovo pagato se c'è, altrimenti fine della prova. */
    public function scadenza(): ?CarbonImmutable
    {
        $data = $this->pagato_fino_al ?? $this->prova_fino_al;

        return $data ? CarbonImmutable::parse($data) : null;
    }

    /** "prova" o "rinnovo" */
    public function tipoScadenza(): string
    {
        return $this->pagato_fino_al ? 'rinnovo' : 'prova';
    }

    /** Giorni alla scadenza (negativi se passata). */
    public function giorniAllaScadenza(): ?int
    {
        $s = $this->scadenza();

        return $s ? (int) CarbonImmutable::today()->diffInDays($s, false) : null;
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
