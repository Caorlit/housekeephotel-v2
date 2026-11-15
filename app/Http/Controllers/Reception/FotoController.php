<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Support\CurrentHotel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Le foto delle segnalazioni NON sono pubbliche (possono ritrarre oggetti degli ospiti):
 * stanno in storage/app/private e si vedono solo da utenti loggati dello stesso hotel.
 */
class FotoController extends Controller
{
    public function show(Issue $issue, CurrentHotel $current): StreamedResponse
    {
        // Doppia sicurezza oltre al filtro automatico.
        abort_unless($current->has() && $issue->hotel_id === $current->id(), 404);
        // Il route model binding passa dal filtro per hotel: un altro hotel riceve 404.
        abort_if(! $issue->foto_path || ! Storage::disk('local')->exists($issue->foto_path), 404);

        return Storage::disk('local')->response($issue->foto_path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /** Foto inoltrata (es. al manutentore): accesso solo con il link firmato e non scaduto. */
    public function condivisa(Issue $issue): StreamedResponse
    {
        abort_if(! $issue->foto_path || ! Storage::disk('local')->exists($issue->foto_path), 404);

        return Storage::disk('local')->response($issue->foto_path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    /** Link firmato con scadenza fissa (30 giorni dalla segnalazione): sempre uguale, così il tabellone non cambia a vuoto. */
    public static function linkCondiviso(Issue $issue): ?string
    {
        if (! $issue->foto_path) {
            return null;
        }
        $scadenza = $issue->created_at->copy()->addDays(30);

        return $scadenza->isPast() ? null : URL::temporarySignedRoute('foto.condivisa', $scadenza, ['issue' => $issue->id]);
    }
}
