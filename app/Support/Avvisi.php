<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AvvisoSuperadmin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Email ai superadmin. Un problema di invio non deve mai bloccare l'app:
 * l'errore finisce nel log e basta.
 */
class Avvisi
{
    /** @param list<string> $righe */
    public static function superadmin(string $oggetto, array $righe, ?string $link = null, ?string $etichetta = null, bool $errore = false): bool
    {
        try {
            $destinatari = User::where('ruolo', User::RUOLO_SUPERADMIN)->where('attivo', true)->get();
            if ($destinatari->isNotEmpty()) {
                Notification::send($destinatari, new AvvisoSuperadmin($oggetto, $righe, $link, $etichetta, $errore));
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Invio avviso superadmin non riuscito: '.$e->getMessage(), ['oggetto' => $oggetto]);

            return false;
        }
    }
}
