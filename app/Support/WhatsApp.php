<?php

namespace App\Support;

/**
 * Link "click to chat" di WhatsApp (gratuiti, nessun account Meta):
 * aprono WhatsApp con il messaggio già scritto; l'invio lo fa la persona.
 */
class WhatsApp
{
    /** Numero in formato internazionale senza + (es. "347 123 4567" → "393471234567"). */
    public static function numero(?string $telefono): ?string
    {
        $cifre = preg_replace('/\D/', '', (string) $telefono);
        if ($cifre === '') {
            return null;
        }
        if (str_starts_with($cifre, '00')) {
            $cifre = substr($cifre, 2);
        } elseif (! str_starts_with((string) $telefono, '+') && strlen($cifre) <= 10 && str_starts_with($cifre, '3')) {
            $cifre = '39'.$cifre; // cellulare italiano scritto senza prefisso
        }

        return strlen($cifre) >= 8 ? $cifre : null;
    }

    /** Link a un numero preciso, oppure (senza numero) WhatsApp fa scegliere il contatto. */
    public static function link(string $testo, ?string $telefono = null): string
    {
        $numero = self::numero($telefono);

        return 'https://wa.me/'.($numero ?? '').'?text='.rawurlencode($testo);
    }
}
