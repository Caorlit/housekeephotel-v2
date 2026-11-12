<?php

namespace App\Models\Concerns;

use App\Models\Hotel;
use App\Support\CurrentHotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isolamento tra hotel.
 *
 * Quando la richiesta ha un hotel attivo, ogni query viene filtrata per
 * hotel_id e ogni nuovo record riceve automaticamente l'hotel_id corretto.
 * Senza hotel attivo (console, superadmin) il filtro non si applica:
 * per questo le rotte della reception e delle addette passano SEMPRE
 * da un middleware che imposta l'hotel.
 */
trait BelongsToHotel
{
    public static function bootBelongsToHotel(): void
    {
        static::addGlobalScope('hotel', function (Builder $builder) {
            $current = app(CurrentHotel::class);
            if ($current->has()) {
                $builder->where($builder->getModel()->getTable().'.hotel_id', $current->id());
            }
        });

        static::creating(function ($model) {
            $current = app(CurrentHotel::class);
            if ($current->has()) {
                if ($model->hotel_id && (int) $model->hotel_id !== $current->id()) {
                    throw new \LogicException('Tentativo di scrivere dati di un altro hotel.');
                }
                $model->hotel_id = $current->id();
            }
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
