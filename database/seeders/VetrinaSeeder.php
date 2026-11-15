<?php

namespace Database\Seeders;

use App\Models\DayGeneration;
use App\Models\EventLog;
use App\Models\Hotel;
use App\Models\Issue;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\Stay;
use App\Models\User;
use App\Support\CurrentHotel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Hotel di esempio per gli screenshot del sito (nomi inventati).
 * Uso: DB_DATABASE=database/vetrina.sqlite php artisan migrate:fresh --seeder=VetrinaSeeder
 * Foto di esempio: storage/app/private/vetrina/*.jpg (facoltative).
 */
class VetrinaSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::create(['nome' => 'Hotel Bellavista', 'fuso_orario' => 'Europe/Rome']);
        app(CurrentHotel::class)->set($hotel);
        User::create(['hotel_id' => $hotel->id, 'nome' => 'Giulia', 'email' => 'reception@vetrina.test', 'password' => 'vetrina1234', 'ruolo' => 'reception']);

        foreach ([1 => 12, 2 => 12, 3 => 10] as $piano => $n) {
            foreach (range(1, $n) as $i) {
                Room::create(['numero' => (string) ($piano * 100 + $i), 'piano' => $piano]);
            }
        }
        $staff = collect([['Maria', 'it'], ['Olena', 'uk'], ['Rina', 'bn'], ['Ana', 'es']])
            ->mapWithKeys(fn ($a) => [$a[0] => Staff::create(['nome' => $a[0], 'lingua' => $a[1]])]);
        file_put_contents(storage_path('app/vetrina-token.txt'), $staff['Maria']->token_accesso);

        $oggi = CarbonImmutable::parse($hotel->oggi());
        $ora = fn (string $hm) => CarbonImmutable::parse($oggi->toDateString().' '.$hm, 'Europe/Rome')->utc();
        $camera = fn (string $n) => Room::where('numero', $n)->first();

        // [numero, addetta, tipo, stato, inizio, fine, urgente, nota]
        $giornata = [
            ['101', 'Maria', 'partenza', 'pronta', '08:35', '09:10'],
            ['102', 'Maria', 'fermata', 'in_pulizia', '09:40', null, false, 'Cambiare gli asciugamani anche se sono appesi'],
            ['103', 'Maria', 'partenza', 'da_fare', null, null, true],
            ['104', 'Maria', 'fermata', 'pronta', '09:12', '09:31'],
            ['106', 'Maria', 'partenza', 'da_fare'],
            ['107', 'Maria', 'fermata', 'da_fare'],
            ['109', 'Maria', 'partenza', 'da_fare'],
            ['110', 'Maria', 'fermata', 'da_fare'],
            ['201', 'Olena', 'partenza', 'pronta', '08:30', '09:02'],
            ['202', 'Olena', 'partenza', 'pronta', '09:05', '09:38'],
            ['204', 'Olena', 'fermata', 'in_pulizia', '09:45'],
            ['205', 'Olena', 'partenza', 'da_fare'],
            ['207', 'Olena', 'fermata', 'da_fare', null, null, false, 'Culla da togliere'],
            ['208', 'Olena', 'partenza', 'da_fare'],
            ['210', 'Olena', 'fermata', 'da_fare'],
            ['301', 'Rina', 'fermata', 'pronta', '08:40', '08:58'],
            ['302', 'Rina', 'partenza', 'in_pulizia', '09:20'],
            ['303', 'Rina', 'partenza', 'da_fare'],
            ['305', 'Rina', 'fermata', 'da_fare'],
            ['306', 'Ana', 'partenza', 'pronta', '08:45', '09:20'],
            ['307', 'Ana', 'fermata', 'da_fare'],
            ['308', 'Ana', 'partenza', 'da_fare', null, null, true],
            ['309', 'Ana', 'fermata', 'da_fare'],
        ];
        foreach ($giornata as $g) {
            [$n, $chi, $tipo, $stato] = $g;
            RoomDay::create([
                'room_id' => $camera($n)->id, 'data' => $oggi->toDateString(), 'tipo' => $tipo, 'stato' => $stato,
                'staff_id' => $staff[$chi]->id, 'urgente' => $g[6] ?? false, 'nota_reception' => $g[7] ?? null,
            ])->forceFill([
                'iniziata_alle' => isset($g[4]) ? $ora($g[4]) : null,
                'finita_alle' => isset($g[5]) ? $ora($g[5]) : null,
                'stato_cambiato_alle' => isset($g[5]) ? $ora($g[5]) : (isset($g[4]) ? $ora($g[4]) : null),
            ])->save();
        }
        DayGeneration::create(['data' => $oggi->toDateString()]);

        // Segnalazioni con foto di esempio (se presenti).
        foreach ([['208', 'Olena', 'Il rubinetto del bagno perde', 'rubinetto.jpg', '09:27'], ['102', 'Maria', 'Occhiali dimenticati sul comodino', 'occhiali.jpg', '09:51']] as [$n, $chi, $nota, $foto, $hm]) {
            $rd = RoomDay::delGiorno($oggi->toDateString())->where('room_id', $camera($n)->id)->first();
            $path = null;
            if (file_exists($sorgente = storage_path('app/private/vetrina/'.$foto))) {
                $path = 'segnalazioni/'.$hotel->id.'/'.$oggi->format('Y-m').'/'.$foto;
                Storage::disk('local')->put($path, file_get_contents($sorgente));
            }
            Issue::create(['room_day_id' => $rd->id, 'staff_id' => $staff[$chi]->id, 'nota' => $nota, 'foto_path' => $path, 'stato' => 'aperta'])
                ->forceFill(['created_at' => $ora($hm)])->save();
        }

        // Prenotazioni per la previsione dei prossimi giorni (le camere restano alle stesse addette).
        foreach (['102' => [-2, 3, 1], '104' => [-1, 5, 2], '107' => [-3, 2, 1], '110' => [-1, 4, 1], '103' => [0, 4, 2], '106' => [0, 6, 2], '109' => [0, 3, 1], '101' => [1, 5, 2], '105' => [1, 3, 0], '111' => [2, 6, 2]] as $n => [$a, $p, $o]) {
            if (! RoomDay::delGiorno($oggi->toDateString())->where('room_id', $camera($n)->id)->exists()) {
                RoomDay::create(['room_id' => $camera($n)->id, 'data' => $oggi->subDays(3)->toDateString(), 'tipo' => 'fermata', 'stato' => 'pronta', 'staff_id' => $staff['Maria']->id]);
            }
            Stay::create(['room_id' => $camera($n)->id, 'arrivo' => $oggi->addDays($a)->toDateString(), 'partenza' => $oggi->addDays($p)->toDateString(), 'riassetto_ogni' => $o]);
        }

        EventLog::registra('vetrina', [], ['nota' => 'dati di esempio per gli screenshot']);
    }
}
