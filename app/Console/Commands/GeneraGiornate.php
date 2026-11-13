<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Services\GeneratoreGiornate;
use App\Support\CurrentHotel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Crea le camere di oggi dalle prenotazioni, per ogni hotel (nel suo fuso orario). */
#[Signature('hk:genera-giornate')]
#[Description('Genera le camere del giorno dalle prenotazioni (Cambio e Riassetto)')]
class GeneraGiornate extends Command
{
    public function handle(GeneratoreGiornate $generatore, CurrentHotel $current): int
    {
        foreach (Hotel::where('attivo', true)->get() as $hotel) {
            $current->set($hotel);
            $n = $generatore->genera($hotel, $hotel->oggi());
            if ($n > 0) {
                $this->info("{$hotel->nome}: {$n} camere create per oggi.");
            }
        }
        $current->set(null);

        return self::SUCCESS;
    }
}
