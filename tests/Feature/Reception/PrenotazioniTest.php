<?php

namespace Tests\Feature\Reception;

use App\Models\Hotel;
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

class PrenotazioniTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private User $user;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-10 08:00', 'Europe/Rome'));
        $this->hotel = Hotel::factory()->create();
        $this->user = User::factory()->create(['hotel_id' => $this->hotel->id]);
        $this->room = Room::factory()->create(['hotel_id' => $this->hotel->id, 'numero' => '104']);
        $this->senzaHotel();
    }

    private function dati(array $extra = []): array
    {
        return $extra + ['room_id' => $this->room->id, 'arrivo' => '2026-11-10', 'partenza' => '2026-11-15', 'riassetto_ogni' => 2, 'dal' => '2026-11-09'];
    }

    private function giorno(string $data): ?RoomDay
    {
        app(CurrentHotel::class)->set($this->hotel);
        $rd = RoomDay::delGiorno($data)->where('room_id', $this->room->id)->first();
        $this->senzaHotel();

        return $rd;
    }

    public function test_pagina_e_creazione(): void
    {
        $this->actingAs($this->user)->get('/reception/prenotazioni')->assertOk()->assertSee('planning-config', false);

        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati(['riferimento' => 'GF7IB3', 'pax' => 2]))
            ->assertOk()
            ->assertJsonPath('soggiorni.0.riferimento', 'GF7IB3')
            ->assertJsonPath('soggiorni.0.riassetto_ogni', 2);
    }

    public function test_sovrapposizione_rifiutata_ma_partenza_e_arrivo_stesso_giorno_ok(): void
    {
        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati())->assertOk();

        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati(['arrivo' => '2026-11-14', 'partenza' => '2026-11-16']))
            ->assertStatus(422)->assertJsonValidationErrors(['arrivo' => 'già occupata dal 10/11 al 15/11']);

        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati(['arrivo' => '2026-11-15', 'partenza' => '2026-11-17']))->assertOk();
    }

    public function test_partenza_prima_dell_arrivo_rifiutata(): void
    {
        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati(['partenza' => '2026-11-10']))->assertJsonValidationErrors('partenza');
    }

    public function test_una_prenotazione_inserita_dopo_che_il_giorno_e_preparato_compare_lo_stesso(): void
    {
        // La reception apre la preparazione di oggi (giorno generato)...
        $this->actingAs($this->user)->get('/reception/giornata')->assertOk();
        // ...poi inserisce una partenza di oggi.
        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati(['arrivo' => '2026-11-08', 'partenza' => '2026-11-10']))->assertOk();

        $this->assertSame('partenza', $this->giorno('2026-11-10')?->tipo);
    }

    public function test_modifica_e_cancellazione_riallineano_le_pulizie(): void
    {
        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati(['arrivo' => '2026-11-08', 'partenza' => '2026-11-12', 'riassetto_ogni' => 1]))->assertOk();
        $this->actingAs($this->user)->get('/reception/giornata')->assertOk();
        $this->assertSame('fermata', $this->giorno('2026-11-10')?->tipo);

        $stay = Stay::withoutGlobalScopes()->first();
        // Partenza anticipata a oggi.
        $this->actingAs($this->user)->putJson("/reception/prenotazioni/{$stay->id}", $this->dati(['arrivo' => '2026-11-08', 'partenza' => '2026-11-10', 'riassetto_ogni' => 1]))->assertOk();
        $this->assertSame('partenza', $this->giorno('2026-11-10')?->tipo);

        $this->actingAs($this->user)->deleteJson("/reception/prenotazioni/{$stay->id}", ['dal' => '2026-11-09'])->assertOk()->assertJsonCount(0, 'soggiorni');
        $this->assertNull($this->giorno('2026-11-10'));
    }

    public function test_non_si_toccano_prenotazioni_o_camere_di_un_altro_hotel(): void
    {
        $altro = Hotel::factory()->create();
        $roomAltro = Room::factory()->create(['hotel_id' => $altro->id]);
        $stayAltro = Stay::factory()->create(['hotel_id' => $altro->id, 'room_id' => $roomAltro->id]);
        $this->senzaHotel();

        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati(['room_id' => $roomAltro->id]))->assertJsonValidationErrors('room_id');
        $this->actingAs($this->user)->deleteJson("/reception/prenotazioni/{$stayAltro->id}")->assertNotFound();
        $this->assertTrue(Stay::withoutGlobalScopes()->whereKey($stayAltro->id)->exists());
    }

    public function test_sostituisci_solo_per_un_giorno_poi_la_camera_torna_alla_titolare(): void
    {
        $maria = Staff::factory()->create(['hotel_id' => $this->hotel->id, 'nome' => 'Maria']);
        $olena = Staff::factory()->create(['hotel_id' => $this->hotel->id, 'nome' => 'Olena']);
        $this->senzaHotel();

        // Soggiorno con riassetto ogni giorno; Maria fa la camera il 9.
        $this->actingAs($this->user)->postJson('/reception/prenotazioni', $this->dati(['arrivo' => '2026-11-08', 'partenza' => '2026-11-13', 'riassetto_ogni' => 1]))->assertOk();
        RoomDay::withoutGlobalScopes()->forceCreate(['hotel_id' => $this->hotel->id, 'room_id' => $this->room->id, 'data' => '2026-11-09', 'tipo' => 'fermata', 'staff_id' => $maria->id]);

        // Il 10 la camera va da sola a Maria; Maria è libera: passa a Olena.
        $this->actingAs($this->user)->get('/reception/giornata')->assertOk();
        $this->assertSame($maria->id, $this->giorno('2026-11-10')->staff_id);
        $this->actingAs($this->user)->postJson('/reception/giornata/2026-11-10/sostituisci', ['da' => $maria->id, 'a' => $olena->id])
            ->assertOk()->assertJsonPath('messaggio', '1 camere di Maria passate a Olena.');
        $this->assertSame($olena->id, $this->giorno('2026-11-10')->staff_id);

        // L'11 torna a Maria.
        app(CurrentHotel::class)->set($this->hotel);
        app(GeneratoreGiornate::class)->genera($this->hotel, '2026-11-11');
        $this->senzaHotel();
        $this->assertSame($maria->id, $this->giorno('2026-11-11')->staff_id);
    }
}
