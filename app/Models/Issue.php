<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Segnalazione di un problema in una camera. */
class Issue extends Model
{
    use BelongsToHotel, HasFactory;

    public const APERTA = 'aperta';

    public const PRESA_IN_CARICO = 'presa_in_carico';

    public const RISOLTA = 'risolta';

    protected $fillable = ['hotel_id', 'room_day_id', 'staff_id', 'nota', 'foto_path', 'stato', 'client_action_id'];

    protected function casts(): array
    {
        return [
            'letta_alle' => 'datetime',
            'risolta_alle' => 'datetime',
        ];
    }

    public function roomDay(): BelongsTo
    {
        return $this->belongsTo(RoomDay::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
