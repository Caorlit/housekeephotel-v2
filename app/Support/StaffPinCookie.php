<?php

namespace App\Support;

use App\Models\Staff;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Cookie "dispositivo ricordato" dopo il PIN (90 giorni).
 * Si invalida da solo se cambiano il PIN o il link dell'addetta.
 */
class StaffPinCookie
{
    public const GIORNI = 90;

    public static function nome(Staff $staff): string
    {
        return 'hk_pin_'.$staff->id;
    }

    private static function impronta(Staff $staff): string
    {
        return hash_hmac('sha256', $staff->id.'|'.$staff->pin.'|'.$staff->token_accesso, config('app.key'));
    }

    public static function crea(Staff $staff): Cookie
    {
        return cookie(self::nome($staff), self::impronta($staff), self::GIORNI * 24 * 60, '/s/', null, null, true, false, 'lax');
    }

    public static function valido(Request $request, Staff $staff): bool
    {
        $valore = $request->cookie(self::nome($staff));

        return is_string($valore) && hash_equals(self::impronta($staff), $valore);
    }
}
