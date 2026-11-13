<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/** Segna che le camere di quel giorno sono già state generate dalle prenotazioni. */
class DayGeneration extends Model
{
    use BelongsToHotel;

    public const UPDATED_AT = null;

    protected $fillable = ['hotel_id', 'data'];

    protected function casts(): array
    {
        return ['data' => 'date:Y-m-d'];
    }
}
