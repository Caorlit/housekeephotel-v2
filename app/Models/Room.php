<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Anagrafica camera. */
class Room extends Model
{
    use BelongsToHotel, HasFactory;

    protected $fillable = ['hotel_id', 'numero', 'piano', 'note_fisse', 'attiva'];

    protected function casts(): array
    {
        return ['attiva' => 'boolean', 'piano' => 'integer'];
    }

    public function days(): HasMany
    {
        return $this->hasMany(RoomDay::class);
    }
}
