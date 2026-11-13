<?php

namespace Tests\Feature;

use App\Console\Commands\ImpostaEmail;
use Dotenv\Dotenv;
use Tests\TestCase;

class ImpostaEmailTest extends TestCase
{
    /** Le password con simboli strani devono essere rilette identiche. */
    public function test_password_con_simboli_rilette_identiche(): void
    {
        foreach (['semplice123', 'con$dollaro&e/barra', 'apice\'e"doppio\\back$x', '${APP_NAME}#non commento'] as $pw) {
            $env = ImpostaEmail::imposta("APP_NAME=Prova\nMAIL_PASSWORD=\nMAIL_HOST=smtp.hostinger.com\n", 'MAIL_PASSWORD', $pw);
            $letto = Dotenv::parse($env);

            $this->assertSame($pw, $letto['MAIL_PASSWORD'], "Password: {$pw}");
            $this->assertSame('smtp.hostinger.com', $letto['MAIL_HOST']);
        }
    }

    public function test_chiave_mancante_viene_aggiunta(): void
    {
        $env = ImpostaEmail::imposta("APP_NAME=Prova\n", 'MAIL_USERNAME', 'info@housekeephotel.com');
        $this->assertSame('info@housekeephotel.com', Dotenv::parse($env)['MAIL_USERNAME']);
    }
}
