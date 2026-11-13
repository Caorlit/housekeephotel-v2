<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Services\Statistiche;
use App\Support\Avvisi;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Ogni lunedì: i numeri degli ultimi 7 giorni di ogni hotel, ai superadmin. */
#[Signature('hk:riepilogo-settimanale')]
#[Description('Manda ai superadmin il riepilogo degli ultimi 7 giorni per hotel')]
class RiepilogoSettimanale extends Command
{
    public function handle(Statistiche $statistiche): int
    {
        $righe = [];
        foreach (Hotel::where('attivo', true)->orderBy('nome')->get() as $hotel) {
            $al = CarbonImmutable::now($hotel->fuso_orario)->subDay()->toDateString();
            $dal = CarbonImmutable::parse($al)->subDays(6)->toDateString();
            $righe[] = $statistiche->riga($hotel, $statistiche->calcola($hotel, $dal, $al));
        }

        if (! $righe) {
            return self::SUCCESS;
        }

        Avvisi::superadmin('riepilogo della settimana', $righe, route('admin.statistiche'), 'Vedi le statistiche');
        $this->info(implode("\n", $righe));

        return self::SUCCESS;
    }
}
