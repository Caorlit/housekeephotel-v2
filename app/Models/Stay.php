<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Una prenotazione: da qui nascono i Cambi e i Riassetti. */
class Stay extends Model
{
    use BelongsToHotel, HasFactory;

    public const RIASSETTO_PREDEFINITO = 2;

    public const FREQUENZE = [
        1 => 'Ogni giorno',
        2 => 'Ogni 2 giorni',
        3 => 'Ogni 3 giorni',
        0 => 'Nessuno fino alla partenza',
    ];

    protected $fillable = ['hotel_id', 'room_id', 'arrivo', 'partenza', 'riassetto_ogni', 'riferimento', 'pax', 'nota', 'fonte'];

    protected function casts(): array
    {
        return [
            'arrivo' => 'date:Y-m-d',
            'partenza' => 'date:Y-m-d',
            'riassetto_ogni' => 'integer',
            'pax' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomDays(): HasMany
    {
        return $this->hasMany(RoomDay::class);
    }

    /**
     * Che pulizia serve in quella data per questo soggiorno:
     * 'partenza' (Cambio) il giorno della partenza, 'fermata' (Riassetto)
     * ogni N giorni contando dall'arrivo, null altrimenti (arrivo compreso).
     */
    public function pulizia(string $data): ?string
    {
        $d = CarbonImmutable::parse($data)->startOfDay();
        $arrivo = CarbonImmutable::parse($this->arrivo)->startOfDay();
        $partenza = CarbonImmutable::parse($this->partenza)->startOfDay();

        if ($d->equalTo($partenza)) {
            return 'partenza';
        }
        if ($d->lessThanOrEqualTo($arrivo) || $d->greaterThan($partenza) || $this->riassetto_ogni < 1) {
            return null;
        }

        return (int) $arrivo->diffInDays($d) % $this->riassetto_ogni === 0 ? 'fermata' : null;
    }

    /** Soggiorni che toccano la data (dal giorno dopo l'arrivo a quello della partenza). */
    public function scopeAttiviIl(Builder $q, string $data): Builder
    {
        return $q->whereDate('arrivo', '<', $data)->whereDate('partenza', '>=', $data);
    }

    /** Soggiorni della stessa camera che si sovrappongono (le notti in comune). */
    public function scopeSovrapposti(Builder $q, int $roomId, string $arrivo, string $partenza, ?int $tranne = null): Builder
    {
        return $q->where('room_id', $roomId)
            ->whereDate('arrivo', '<', $partenza)
            ->whereDate('partenza', '>', $arrivo)
            ->when($tranne, fn ($q) => $q->whereKeyNot($tranne));
    }
}
