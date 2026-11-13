<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Configura la casella email nel file .env senza editor (sul server non c'è nano).
 * La password si scrive "al buio" e non finisce nella cronologia dei comandi.
 */
#[Signature('hk:imposta-email {indirizzo? : es. info@housekeephotel.com}')]
#[Description('Imposta casella e password email nel file .env')]
class ImpostaEmail extends Command
{
    public function handle(): int
    {
        $indirizzo = $this->argument('indirizzo') ?: $this->ask('Indirizzo della casella', 'info@housekeephotel.com');
        if (! filter_var($indirizzo, FILTER_VALIDATE_EMAIL)) {
            $this->error('Indirizzo non valido.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password della casella (mentre scrivi non compare niente)');
        if ($password === '') {
            $this->error('Password vuota: niente modificato.');

            return self::FAILURE;
        }

        $file = $this->laravel->environmentFilePath();
        $env = file_exists($file) ? file_get_contents($file) : '';
        foreach ([
            'MAIL_USERNAME' => $indirizzo,
            'MAIL_PASSWORD' => $password,
            'MAIL_FROM_ADDRESS' => $indirizzo,
        ] as $chiave => $valore) {
            $env = self::imposta($env, $chiave, $valore);
        }
        file_put_contents($file, $env);

        $this->call('config:cache');
        $this->info("Fatto: le email partiranno da {$indirizzo}. Ora prova con: php artisan hk:prova-email");

        return self::SUCCESS;
    }

    /** Sostituisce (o aggiunge) CHIAVE=valore, con le virgolette giuste per qualsiasi simbolo. */
    public static function imposta(string $env, string $chiave, string $valore): string
    {
        $riga = $chiave.'='.self::virgolette($valore);
        $schema = '/^'.preg_quote($chiave, '/').'=.*$/m';

        return preg_match($schema, $env)
            ? preg_replace_callback($schema, fn () => $riga, $env)
            : rtrim($env, "\n")."\n".$riga."\n";
    }

    private static function virgolette(string $valore): string
    {
        // Tra apici singoli il valore è letto alla lettera (anche $ \ " &).
        if (! str_contains($valore, "'")) {
            return "'".$valore."'";
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $valore).'"';
    }
}
