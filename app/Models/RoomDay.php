<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Lo stato di una camera in un giorno. */
class RoomDay extends Model
{
    use BelongsToHotel, HasFactory;

    public const DA_FARE = 'da_fare';

    public const IN_PULIZIA = 'in_pulizia';

    public const PRONTA = 'pronta';

    public const STATI = [self::DA_FARE, self::IN_PULIZIA, self::PRONTA];

    // Valori nel database; nell'interfaccia: Cambio, Riassetto, Extra.
    public const TIPI = ['partenza', 'fermata', 'extra'];

    public const NOMI_TIPI = ['partenza' => 'Cambio', 'fermata' => 'Riassetto', 'extra' => 'Extra'];

    protected $fillable = [
        'hotel_id', 'room_id', 'stay_id', 'data', 'tipo', 'stato', 'staff_id', 'sostituzione', 'urgente', 'nota_reception',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date:Y-m-d',
            'urgente' => 'boolean',
            'sostituzione' => 'boolean',
            'iniziata_alle' => 'datetime',
            'finita_alle' => 'datetime',
            'stato_cambiato_alle' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(Stay::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function scopeDelGiorno(Builder $q, string $data): Builder
    {
        return $q->whereDate('room_days.data', $data);
    }

    /** Ordine per piano e numero (posizioni fisse, per il tabellone). */
    public function scopePerNumero(Builder $q): Builder
    {
        return $q->join('rooms', 'rooms.id', '=', 'room_days.room_id')
            ->orderBy('rooms.piano')
            ->orderByRaw('LENGTH(rooms.numero), rooms.numero')
            ->select('room_days.*');
    }

    /** Ordine per le addette: urgenti in cima, poi piano e numero. */
    public function scopeOrdinate(Builder $q): Builder
    {
        return $q->join('rooms', 'rooms.id', '=', 'room_days.room_id')
            ->orderByDesc('room_days.urgente')
            ->orderBy('rooms.piano')
            ->orderByRaw('LENGTH(rooms.numero), rooms.numero')
            ->select('room_days.*');
    }
}
