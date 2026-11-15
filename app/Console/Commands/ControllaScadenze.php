<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Support\Avvisi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Scadenze di prova e rinnovi (pagamenti gestiti a mano):
 * un'email ai superadmin 7 giorni prima e una dopo la scadenza, una sola volta ciascuna.
 */
#[Signature('hk:controlla-scadenze {--giorni=7}')]
#[Description('Avvisa delle prove gratuite e dei rinnovi in scadenza')]
class ControllaScadenze extends Command
{
    public function handle(): int
    {
        $preavviso = (int) $this->option('giorni');

        foreach (Hotel::where('attivo', true)->get() as $hotel) {
            $scadenza = $hotel->scadenza();
            $giorni = $hotel->giorniAllaScadenza();
            if (! $scadenza || $giorni > $preavviso) {
                continue;
            }

            $fase = $giorni < 0 ? 'scaduta' : 'preavviso';
            $chiave = $scadenza->toDateString().':'.$fase;
            if ($hotel->avviso_scadenza === $chiave) {
                continue; // già avvisato per questa scadenza e questa fase
            }

            $cosa = $hotel->tipoScadenza() === 'prova' ? 'La prova gratuita' : 'Il rinnovo annuale';
            $quando = $scadenza->format('d/m/Y');
            Avvisi::superadmin(
                $fase === 'scaduta' ? "{$hotel->nome}: scadenza passata" : "{$hotel->nome}: scadenza tra {$giorni} giorni",
                array_filter([
                    $fase === 'scaduta'
                        ? "{$cosa} di {$hotel->nome} è scaduta il {$quando} e non risulta ancora il pagamento."
                        : "{$cosa} di {$hotel->nome} scade il {$quando}: è il momento di inviare i dati per il pagamento.",
                    $hotel->piano ? 'Piano: '.Hotel::PIANI[$hotel->piano].'.' : 'Piano non indicato.',
                    $hotel->email ? "Email dell'hotel: {$hotel->email}" : null,
                    'Quando ricevi il pagamento, premi "Registra pagamento annuale" nella scheda dell\'hotel.',
                ]),
                route('admin.hotel.show', $hotel), 'Apri l\'hotel',
            );

            $hotel->forceFill(['avviso_scadenza' => $chiave])->save();
            $this->info("Avviso {$fase}: {$hotel->nome} ({$quando})");
        }

        return self::SUCCESS;
    }
}
