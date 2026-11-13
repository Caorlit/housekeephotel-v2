<?php

namespace App\Console\Commands;

use App\Support\Avvisi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Verifica la configurazione email: manda un messaggio di prova ai superadmin. */
#[Signature('hk:prova-email')]
#[Description('Manda un\'email di prova ai superadmin')]
class ProvaEmail extends Command
{
    public function handle(): int
    {
        $ok = Avvisi::superadmin('email di prova', [
            'Se leggi questo messaggio, l\'invio delle email da Housekeep funziona.',
            'Mittente: '.config('mail.from.address').' · server: '.config('mail.mailers.smtp.host'),
        ], url('/admin'), 'Apri Housekeep');

        if ($ok) {
            $this->info('Email inviata: controlla la casella dei superadmin (anche nello spam).');

            return self::SUCCESS;
        }
        $this->error('Invio non riuscito: controlla MAIL_USERNAME e MAIL_PASSWORD nel file .env (dettagli in storage/logs).');

        return self::FAILURE;
    }
}
