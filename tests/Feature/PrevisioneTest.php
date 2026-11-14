<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\Stay;
use App\Services\GeneratoreGiornate;
use App\Support\CurrentHotel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrevisioneTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private Staff $maria;

    private Staff $olena;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-14 08:00', 'Europe/Rome')); // sabato
        $this->hotel = Hotel::factory()->create();
        app(CurrentHotel::class)->set($this->hotel);
        $this->maria = Staff::factory()->create(['hotel_id' => $this->hotel->id, 'nome' => 'Maria']);
        $this->olena = Staff::factory()->create(['hotel_id' => $this->hotel->id, 'nome' => 'Olena']);
    }

    private function camera(string $numero, Staff $titolare): Room
    {
        $room = Room::factory()->create(['hotel_id' => $this->hotel->id, 'numero' => $numero, 'piano' => 1]);
        // Oggi la camera l'ha fatta la titolare.
        RoomDay::create(['room_id' => $room->id, 'data' => '2026-11-14', 'tipo' => 'fermata', 'staff_id' => $titolare->id]);

        return $room;
    }

    private function prossimi(Staff $staff): array
    {
        app(CurrentHotel::class)->set(null);
        $r = $this->getJson("/s/{$staff->token_accesso}/camere")->assertOk()->json('prossimi');
        app(CurrentHotel::class)->set($this->hotel);

        return collect($r)->mapWithKeys(fn ($g) => [$g['data'] => collect($g['camere'])->map(fn ($c) => $c['tipo'].' '.$c['numero'])->all()])->all();
    }

    public function test_sette_giorni_da_domani_con_cambi_e_riassetti_della_titolare(): void
    {
        $r104 = $this->camera('104', $this->maria);
        $r105 = $this->camera('105', $this->olena);
        Stay::factory()->create(['hotel_id' => $this->hotel->id, 'room_id' => $r104->id, 'arrivo' => '2026-11-13', 'partenza' => '2026-11-18', 'riassetto_ogni' => 2]);
        Stay::factory()->create(['hotel_id' => $this->hotel->id, 'room_id' => $r105->id, 'arrivo' => '2026-11-13', 'partenza' => '2026-11-16', 'riassetto_ogni' => 1]);

        $p = $this->prossimi($this->maria);

        $this->assertSame(['2026-11-15', '2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20', '2026-11-21'], array_keys($p));
        $this->assertSame(['fermata 104'], $p['2026-11-15']);   // 2 giorni dall'arrivo
        $this->assertSame([], $p['2026-11-16']);
        $this->assertSame(['fermata 104'], $p['2026-11-17']);
        $this->assertSame(['partenza 104'], $p['2026-11-18']);
        $this->assertSame(['fermata 105', 'partenza 105'], [$this->prossimi($this->olena)['2026-11-15'][0], $this->prossimi($this->olena)['2026-11-16'][0]], 'La 105 è di Olena, non di Maria');
    }

    public function test_un_giorno_gia_preparato_mostra_le_scelte_della_reception(): void
    {
        $r104 = $this->camera('104', $this->maria);
        Stay::factory()->create(['hotel_id' => $this->hotel->id, 'room_id' => $r104->id, 'arrivo' => '2026-11-13', 'partenza' => '2026-11-18', 'riassetto_ogni' => 2]);

        // La reception prepara già domenica e sposta la 104 a Olena (Maria è libera).
        app(GeneratoreGiornate::class)->genera($this->hotel, '2026-11-15');
        RoomDay::delGiorno('2026-11-15')->where('room_id', $r104->id)->update(['staff_id' => $this->olena->id, 'sostituzione' => true]);

        $this->assertSame([], $this->prossimi($this->maria)['2026-11-15']);
        $this->assertSame(['fermata 104'], $this->prossimi($this->olena)['2026-11-15']);
        // Martedì (non ancora preparato) torna a Maria.
        $this->assertSame(['fermata 104'], $this->prossimi($this->maria)['2026-11-17']);
    }

    public function test_camere_inserite_a_mano_nei_giorni_futuri(): void
    {
        $r201 = Room::factory()->create(['hotel_id' => $this->hotel->id, 'numero' => '201', 'piano' => 2]);
        RoomDay::create(['room_id' => $r201->id, 'data' => '2026-11-19', 'tipo' => 'extra', 'staff_id' => $this->maria->id]);

        $this->assertSame(['extra 201'], $this->prossimi($this->maria)['2026-11-19']);
    }

    public function test_la_schermata_mostra_la_sezione_con_l_avviso_nella_lingua_dell_addetta(): void
    {
        $this->maria->update(['lingua' => 'es']);
        app(CurrentHotel::class)->set(null);

        $html = $this->get("/s/{$this->maria->token_accesso}")->assertOk()->assertSee('titolo-prossimi', false)->getContent();
        preg_match('#<script type="application/json" id="hk-config">(.*?)</script>#s', $html, $m);
        $cfg = json_decode($m[1], true);

        $this->assertSame('Próximos días', $cfg['t']['prossimi_giorni']);
        $this->assertSame('Previsión: puede cambiar', $cfg['t']['previsione_avviso']);
        $this->assertSame('dom. 15', $cfg['dati']['prossimi'][0]['etichetta']);
    }
}
