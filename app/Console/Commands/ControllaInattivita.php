<?php

namespace App\Console\Commands;

use App\Models\EventLog;
use App\Models\Hotel;
use App\Support\Avvisi;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Avvisa i superadmin se un hotel attivo non usa l'app da 7 giorni
 * (nessuna camera toccata, nessuna preparazione, nessuna segnalazione...).
 * Una sola email per ogni periodo di inattività.
 */
#[Signature('hk:controlla-inattivita {--giorni=7}')]
#[Description('Avvisa se un hotel non usa l\'app da N giorni')]
class ControllaInattivita extends Command
{
    public function handle(): int
    {
        $giorni = (int) $this->option('giorni');
        $limite = now()->subDays($giorni);

        foreach (Hotel::where('attivo', true)->get() as $hotel) {
            $ultima = EventLog::withoutGlobalScopes()->where('hotel_id', $hotel->id)->max('created_at');
            $ultima = $ultima ? CarbonImmutable::parse($ultima) : CarbonImmutable::parse($hotel->created_at);

            $inattivo = $ultima->lt($limite);
            $giaAvvisato = $hotel->avviso_inattivita_il && CarbonImmutable::parse($hotel->avviso_inattivita_il)->gt($ultima);
            if (! $inattivo || $giaAvvisato) {
                continue;
            }

            Avvisi::superadmin("{$hotel->nome} non usa l'app da {$giorni} giorni", [
                "L'ultima attività dell'hotel {$hotel->nome} risale al ".$ultima->timezone($hotel->fuso_orario)->format('d/m/Y H:i').'.',
                'Forse stanno tornando a WhatsApp, oppure l\'hotel è chiuso: vale la pena sentirli.',
            ], route('admin.hotel.show', $hotel), 'Apri l\'hotel');

            $hotel->forceFill(['avviso_inattivita_il' => now()])->save();
            $this->info("Avviso inattività: {$hotel->nome}");
        }

        return self::SUCCESS;
    }
}
