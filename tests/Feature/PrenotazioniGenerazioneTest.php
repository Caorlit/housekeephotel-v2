<?php

namespace Tests\Feature;

use App\Models\DayGeneration;
use App\Models\Hotel;
use App\Models\Issue;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\Stay;
use App\Models\User;
use App\Services\GeneratoreGiornate;
use App\Support\CurrentHotel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrenotazioniGenerazioneTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        // Oggi = 10 novembre, così le date dei test sono leggibili.
        $this->travelTo(CarbonImmutable::parse('2026-11-10 08:00', 'Europe/Rome'));
        $this->hotel = Hotel::factory()->create();
        app(CurrentHotel::class)->set($this->hotel);
        $this->room = Room::factory()->create(['hotel_id' => $this->hotel->id, 'numero' => '104', 'piano' => 1]);
    }

    private function stay(string $arrivo, string $partenza, int $ogni = 2, ?Room $room = null): Stay
    {
        return Stay::factory()->create([
            'hotel_id' => $this->hotel->id, 'room_id' => ($room ?? $this->room)->id,
            'arrivo' => $arrivo, 'partenza' => $partenza, 'riassetto_ogni' => $ogni,
        ]);
    }

    private function gen(): GeneratoreGiornate
    {
        return app(GeneratoreGiornate::class);
    }

    private function tipoIl(string $data, ?Room $room = null): ?string
    {
        return RoomDay::delGiorno($data)->where('room_id', ($room ?? $this->room)->id)->value('tipo');
    }

    public function test_esempio_rossi_dal_10_al_15_riassetto_ogni_2_giorni(): void
    {
        $s = $this->stay('2026-11-10', '2026-11-15', 2);

        $atteso = ['2026-11-10' => null, '2026-11-11' => null, '2026-11-12' => 'fermata', '2026-11-13' => null, '2026-11-14' => 'fermata', '2026-11-15' => 'partenza', '2026-11-16' => null];
        foreach ($atteso as $d => $tipo) {
            $this->assertSame($tipo, $s->pulizia($d), "Giorno {$d}");
        }
    }

    public function test_frequenze_ogni_giorno_e_nessuna(): void
    {
        $ogniGiorno = $this->stay('2026-11-10', '2026-11-13', 1);
        $this->assertSame([null, 'fermata', 'fermata', 'partenza'], array_map(fn ($d) => $ogniGiorno->pulizia($d), ['2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13']));

        $nessuna = $this->stay('2026-11-20', '2026-11-25', 0);
        $this->assertSame([null, null, null, 'partenza'], array_map(fn ($d) => $nessuna->pulizia($d), ['2026-11-20', '2026-11-21', '2026-11-24', '2026-11-25']));
    }

    public function test_una_notte_solo_il_cambio(): void
    {
        $s = $this->stay('2026-11-10', '2026-11-11', 1);
        $this->assertNull($s->pulizia('2026-11-10'));
        $this->assertSame('partenza', $s->pulizia('2026-11-11'));
    }

    public function test_genera_crea_le_camere_una_volta_sola_e_rispetta_le_modifiche_a_mano(): void
    {
        $this->stay('2026-11-08', '2026-11-12', 2); // 10 = riassetto
        $altra = Room::factory()->create(['hotel_id' => $this->hotel->id, 'numero' => '105']);
        $this->stay('2026-11-07', '2026-11-10', 2, $altra); // 10 = cambio

        $this->assertSame(2, $this->gen()->genera($this->hotel, '2026-11-10'));
        $this->assertSame('fermata', $this->tipoIl('2026-11-10'));
        $this->assertSame('partenza', $this->tipoIl('2026-11-10', $altra));

        // La reception toglie il riassetto (ospite: non disturbare): non deve tornare.
        RoomDay::delGiorno('2026-11-10')->where('room_id', $this->room->id)->delete();
        $this->assertSame(0, $this->gen()->genera($this->hotel, '2026-11-10'));
        $this->assertNull($this->tipoIl('2026-11-10'));

        // ...a meno di chiederlo esplicitamente.
        $this->assertSame(1, $this->gen()->genera($this->hotel, '2026-11-10', forza: true));
    }

    public function test_partenza_e_arrivo_lo_stesso_giorno_un_solo_cambio(): void
    {
        $this->stay('2026-11-08', '2026-11-10', 1);
        $this->stay('2026-11-10', '2026-11-14', 1);

        $this->gen()->genera($this->hotel, '2026-11-10');

        $this->assertSame(1, RoomDay::delGiorno('2026-11-10')->count());
        $this->assertSame('partenza', $this->tipoIl('2026-11-10'));
    }

    public function test_la_camera_va_all_ultima_addetta_che_l_ha_pulita(): void
    {
        $maria = Staff::factory()->create(['hotel_id' => $this->hotel->id, 'nome' => 'Maria']);
        RoomDay::create(['room_id' => $this->room->id, 'data' => '2026-11-08', 'tipo' => 'fermata', 'staff_id' => $maria->id]);
        $this->stay('2026-11-06', '2026-11-12', 2);

        $this->gen()->genera($this->hotel, '2026-11-10');

        $this->assertSame($maria->id, RoomDay::delGiorno('2026-11-10')->where('room_id', $this->room->id)->value('staff_id'));
    }

    public function test_addetta_disattivata_non_viene_proposta(): void
    {
        $maria = Staff::factory()->create(['hotel_id' => $this->hotel->id, 'attivo' => false]);
        RoomDay::create(['room_id' => $this->room->id, 'data' => '2026-11-08', 'tipo' => 'fermata', 'staff_id' => $maria->id]);
        $this->stay('2026-11-06', '2026-11-12', 2);

        $this->gen()->genera($this->hotel, '2026-11-10');

        $this->assertNull(RoomDay::delGiorno('2026-11-10')->where('room_id', $this->room->id)->value('staff_id'));
    }

    public function test_prenotazione_accorciata_o_cancellata_riallinea_i_giorni_gia_generati(): void
    {
        $s = $this->stay('2026-11-09', '2026-11-14', 1);
        foreach (['2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13', '2026-11-14'] as $d) {
            $this->gen()->genera($this->hotel, $d);
        }
        $this->assertSame('partenza', $this->tipoIl('2026-11-14'));

        // Partenza anticipata al 12.
        $s->update(['partenza' => '2026-11-12']);
        $this->gen()->sincronizza($this->hotel, $this->room->id, '2026-11-09', '2026-11-14');
        $this->assertSame('fermata', $this->tipoIl('2026-11-11'));
        $this->assertSame('partenza', $this->tipoIl('2026-11-12'));
        $this->assertNull($this->tipoIl('2026-11-13'));
        $this->assertNull($this->tipoIl('2026-11-14'));

        // Cancellata: spariscono le camere da fare, ma non quelle iniziate o con segnalazione.
        RoomDay::delGiorno('2026-11-10')->where('room_id', $this->room->id)->update(['stato' => 'in_pulizia']);
        $rd11 = RoomDay::delGiorno('2026-11-11')->where('room_id', $this->room->id)->first();
        Issue::create(['room_day_id' => $rd11->id, 'stato' => 'aperta']);

        $this->gen()->sincronizza($this->hotel, $this->room->id, '2026-11-09', '2026-11-12', escludi: $s->id);
        $s->delete();

        $this->assertNotNull($this->tipoIl('2026-11-10'), 'iniziata: resta');
        $this->assertNotNull($this->tipoIl('2026-11-11'), 'con segnalazione: resta');
        $this->assertNull($this->tipoIl('2026-11-12'), 'da fare: tolta');
    }

    public function test_sincronizza_non_tocca_le_camere_inserite_a_mano_ne_il_passato(): void
    {
        $this->gen()->genera($this->hotel, '2026-11-10');
        RoomDay::create(['room_id' => $this->room->id, 'data' => '2026-11-10', 'tipo' => 'extra']); // a mano
        $this->stay('2026-11-08', '2026-11-10', 1);

        $this->gen()->sincronizza($this->hotel, $this->room->id, '2026-11-08', '2026-11-10');

        $this->assertSame('extra', $this->tipoIl('2026-11-10'));
        $this->assertSame(0, RoomDay::delGiorno('2026-11-09')->count(), 'Il passato non si rigenera');
    }

    public function test_il_comando_orario_genera_oggi_per_ogni_hotel(): void
    {
        $this->stay('2026-11-08', '2026-11-10', 1);
        app(CurrentHotel::class)->set(null);

        $this->artisan('hk:genera-giornate')->assertSuccessful();

        app(CurrentHotel::class)->set($this->hotel);
        $this->assertSame('partenza', $this->tipoIl('2026-11-10'));
        $this->assertTrue(DayGeneration::whereDate('data', '2026-11-10')->exists());
    }

    public function test_aprendo_il_tabellone_le_camere_di_oggi_compaiono_da_sole(): void
    {
        $this->stay('2026-11-08', '2026-11-10', 1);
        $user = User::factory()->create(['hotel_id' => $this->hotel->id]);
        app(CurrentHotel::class)->set(null);

        $this->actingAs($user)->getJson('/reception/dati')->assertOk()
            ->assertJsonPath('camere.0.numero', '104')
            ->assertJsonPath('camere.0.tipo', 'partenza');
    }
}
