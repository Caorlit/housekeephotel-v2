<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AvvisoSuperadmin;
use Illuminate\Support\Facades\Cache;
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

    private static bool $inCorso = false;

    /**
     * Errore tecnico grave: email ai superadmin, al massimo una all'ora per lo stesso
     * errore e 10 all'ora in tutto, così un guasto non riempie la casella.
     */
    public static function errore(\Throwable $e): void
    {
        if (self::$inCorso) {
            return; // un errore durante l'invio dell'avviso non deve generare altri avvisi
        }
        self::$inCorso = true;

        try {
            $impronta = 'hk-errore:'.md5(get_class($e).'|'.$e->getMessage().'|'.$e->getFile().'|'.$e->getLine());
            if (! Cache::add($impronta, 1, now()->addHour())) {
                return;
            }
            Cache::add('hk-errori-ora', 0, now()->addHour());
            if (Cache::increment('hk-errori-ora') > 10) {
                return;
            }

            $richiesta = app()->runningInConsole() ? 'comando da console' : request()->method().' '.request()->fullUrl();
            self::superadmin('errore tecnico', [
                'Errore: '.mb_substr($e->getMessage(), 0, 300),
                'Tipo: '.class_basename($e).' · '.str_replace(base_path().'/', '', $e->getFile()).':'.$e->getLine(),
                'Dove: '.$richiesta,
                'Quando: '.now()->timezone('Europe/Rome')->format('d/m/Y H:i:s'),
                'Il dettaglio completo è nel log del server (storage/logs).',
            ], errore: true);
        } catch (\Throwable) {
            // mai far fallire la richiesta per colpa dell'avviso
        } finally {
            self::$inCorso = false;
        }
    }
}
