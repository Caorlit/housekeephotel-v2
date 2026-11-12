<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\User;
use App\Support\CurrentHotel;
use Illuminate\Database\Seeder;

/**
 * Hotel di prova con 30 camere, 3 addette e le camere di oggi assegnate.
 * Uso: php artisan db:seed --class=DemoSeeder
 * Stampa i link personali delle addette.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::firstOrCreate(['nome' => 'Hotel Demo'], ['fuso_orario' => 'Europe/Rome']);
        app(CurrentHotel::class)->set($hotel);

        User::firstOrCreate(['email' => 'reception@demo.test'], [
            'hotel_id' => $hotel->id, 'nome' => 'Reception Demo', 'password' => 'demo1234', 'ruolo' => User::RUOLO_RECEPTION,
        ]);

        foreach ([1, 2, 3] as $piano) {
            foreach (range(1, 10) as $n) {
                Room::firstOrCreate(['numero' => (string) ($piano * 100 + $n)], ['piano' => $piano]);
            }
        }

        $addette = collect([['Maria', 'it'], ['Olena', 'uk'], ['Rina', 'bn']])
            ->map(fn ($a) => Staff::firstOrCreate(['nome' => $a[0]], ['lingua' => $a[1]]));

        $oggi = $hotel->oggi();
        $note = [102 => 'Culla da togliere', 205 => 'Ospite allergico: niente profumo'];
        foreach (Room::orderBy('numero')->get() as $i => $room) {
            if ($i % 3 === 2) {
                continue; // non tutte le camere vanno pulite oggi
            }
            RoomDay::firstOrCreate(['room_id' => $room->id, 'data' => $oggi], [
                'tipo' => $i % 2 ? 'fermata' : 'partenza',
                'staff_id' => $addette[$room->piano - 1]->id,
                'urgente' => in_array((int) $room->numero, [104, 301]),
                'nota_reception' => $note[(int) $room->numero] ?? null,
            ]);
        }

        $this->command?->info('Reception: reception@demo.test / demo1234');
        foreach ($addette as $s) {
            $this->command?->info("{$s->nome} ({$s->lingua}): ".$s->linkPersonale());
        }
    }
}
