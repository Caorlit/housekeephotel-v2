<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Richiesta di prova gratuita arrivata dal sito. */
class TrialRequest extends Model
{
    public const STATI = ['nuova' => 'Nuova', 'contattata' => 'Contattata', 'attivata' => 'Attivata', 'scartata' => 'Scartata'];

    protected $fillable = ['nome', 'hotel', 'citta', 'email', 'telefono', 'camere', 'messaggio', 'lingua', 'stato', 'hotel_id'];

    public function hotelCreato(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
