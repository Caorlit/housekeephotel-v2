<?php

namespace Tests\Feature\Reception;

use App\Models\Hotel;
use App\Models\Issue;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\User;
use App\Support\CurrentHotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiornataTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private User $user;

    private string $oggi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hotel = Hotel::factory()->create();
        $this->user = User::factory()->create(['hotel_id' => $this->hotel->id]);
        $this->oggi = $this->hotel->oggi();
        foreach ([1, 2] as $piano) {
            foreach (range(1, 5) as $n) {
                Room::factory()->create(['hotel_id' => $this->hotel->id, 'numero' => (string) ($piano * 100 + $n), 'piano' => $piano]);
            }
        }
    }

    private function addetta(string $nome): Staff
    {
        return Staff::factory()->create(['hotel_id' => $this->hotel->id, 'nome' => $nome]);
    }

    private function room(string $numero): Room
    {
        return Room::withoutGlobalScopes()->where('hotel_id', $this->hotel->id)->where('numero', $numero)->first();
    }

    private function giorni(?string $data = null)
    {
        app(CurrentHotel::class)->set($this->hotel);
        $g = RoomDay::delGiorno($data ?? $this->oggi)->with('room')->get()->keyBy(fn ($g) => $g->room->numero);
        $this->senzaHotel();

        return $g;
    }

    private function azione(string $azione, array $dati, ?string $data = null)
    {
        return $this->actingAs($this->user)->postJson('/reception/giornata/'.($data ?? $this->oggi)."/{$azione}", $dati);
    }

    public function test_la_pagina_si_apre_su_oggi(): void
    {
        $this->actingAs($this->user)->get('/reception/giornata')->assertOk()->assertSee($this->oggi)->assertSee('101');
    }

    public function test_incolla_numeri_con_virgole_spazi_e_a_capo(): void
    {
        $maria = $this->addetta('Maria');

        $this->azione('incolla', ['testo' => "101, 102 103\n104;999", 'tipo' => 'fermata', 'staff_id' => $maria->id])
            ->assertOk()
            ->assertJsonPath('messaggio', '4 camere inserite.')
            ->assertJsonPath('avvisi.0', 'Camere non trovate (aggiungile nella pagina Camere): 999');

        $g = $this->giorni();
        $this->assertEquals([101, 102, 103, 104], $g->keys()->sort()->values()->all());
        $this->assertTrue($g->every(fn ($x) => $x->tipo === 'fermata' && $x->staff_id === $maria->id));
    }

    public function test_pennello_addetta_tipo_e_togli(): void
    {
        $maria = $this->addetta('Maria');
        $ids = [$this->room('201')->id, $this->room('202')->id];

        $this->azione('applica', ['room_ids' => $ids, 'azione' => 'assegna', 'staff_id' => $maria->id])->assertOk();
        $this->assertSame($maria->id, $this->giorni()['201']->staff_id);
        $this->assertSame('partenza', $this->giorni()['201']->tipo);

        $this->azione('applica', ['room_ids' => [$ids[0]], 'azione' => 'tipo', 'tipo' => 'fermata'])->assertOk();
        $this->assertSame('fermata', $this->giorni()['201']->tipo);
        $this->assertSame($maria->id, $this->giorni()['201']->staff_id, 'Cambiare il tipo non toglie l\'addetta');

        $this->azione('applica', ['room_ids' => [$ids[0]], 'azione' => 'assegna', 'staff_id' => null])->assertOk();
        $this->assertNull($this->giorni()['201']->staff_id);

        $this->azione('applica', ['room_ids' => $ids, 'azione' => 'togli'])->assertOk()->assertJsonCount(0, 'avvisi');
        $this->assertCount(0, $this->giorni());
    }

    public function test_non_si_toglie_una_camera_gia_iniziata_o_con_segnalazione(): void
    {
        $this->azione('incolla', ['testo' => '101 102', 'tipo' => 'partenza']);
        $g = $this->giorni();
        RoomDay::withoutGlobalScopes()->whereKey($g['101']->id)->update(['stato' => 'in_pulizia']);
        Issue::withoutGlobalScopes()->forceCreate(['hotel_id' => $this->hotel->id, 'room_day_id' => $g['102']->id, 'stato' => 'aperta']);

        $this->azione('applica', ['room_ids' => [$g['101']->room_id, $g['102']->room_id], 'azione' => 'togli'])
            ->assertOk()->assertJsonCount(2, 'avvisi');
        $this->assertCount(2, $this->giorni());
    }

    public function test_distribuisci_in_modo_equo_e_a_blocchi_per_piano(): void
    {
        $anna = $this->addetta('Anna');
        $bea = $this->addetta('Bea');
        $this->azione('incolla', ['testo' => '101 102 103 104 105 201 202 203 204 205', 'tipo' => 'partenza']);

        $this->azione('distribuisci', ['staff_ids' => [$anna->id, $bea->id]])->assertOk()->assertJsonPath('messaggio', '10 camere distribuite.');

        $g = $this->giorni();
        $this->assertEquals([101, 102, 103, 104, 105], $g->where('staff_id', $anna->id)->keys()->sort()->values()->all());
        $this->assertEquals([201, 202, 203, 204, 205], $g->where('staff_id', $bea->id)->keys()->sort()->values()->all());
    }

    public function test_distribuisci_tiene_conto_di_chi_ne_ha_gia(): void
    {
        $anna = $this->addetta('Anna');
        $bea = $this->addetta('Bea');
        // Anna ne ha già 4; restano 6 libere: per arrivare a 5 e 5, 1 ad Anna e 5 a Bea.
        $this->azione('incolla', ['testo' => '101 102 103 104', 'tipo' => 'partenza', 'staff_id' => $anna->id]);
        $this->azione('incolla', ['testo' => '105 201 202 203 204 205', 'tipo' => 'partenza']);

        $this->azione('distribuisci', ['staff_ids' => [$anna->id, $bea->id]])->assertOk();

        $g = $this->giorni();
        $this->assertSame(5, $g->where('staff_id', $anna->id)->count());
        $this->assertSame(5, $g->where('staff_id', $bea->id)->count());
        $this->assertSame(0, $g->whereNull('staff_id')->count());
    }

    public function test_copia_da_ieri_senza_toccare_quelle_gia_inserite(): void
    {
        $maria = $this->addetta('Maria');
        $ieri = now($this->hotel->fuso_orario)->subDay()->toDateString();
        $this->azione('incolla', ['testo' => '101 102 103', 'tipo' => 'fermata', 'staff_id' => $maria->id], $ieri);
        $this->azione('incolla', ['testo' => '101', 'tipo' => 'partenza']);

        $this->azione('copia-ieri', [])->assertOk()->assertJsonPath('messaggio', 'Copiate 2 camere da ieri.');

        $g = $this->giorni();
        $this->assertCount(3, $g);
        $this->assertSame('partenza', $g['101']->tipo, 'La 101 c\'era già: non viene sovrascritta');
        $this->assertSame('fermata', $g['102']->tipo);
        $this->assertSame($maria->id, $g['103']->staff_id);
        $this->assertSame('da_fare', $g['103']->stato);
    }

    public function test_data_non_valida_404(): void
    {
        $this->azione('copia-ieri', [], '2026-02-31')->assertNotFound();
    }

    public function test_non_si_usano_camere_o_addette_di_un_altro_hotel(): void
    {
        $altro = Hotel::factory()->create();
        $roomAltro = Room::factory()->create(['hotel_id' => $altro->id, 'numero' => '101']);
        $staffAltro = Staff::factory()->create(['hotel_id' => $altro->id]);
        $this->senzaHotel();

        $this->azione('applica', ['room_ids' => [$roomAltro->id], 'azione' => 'assegna'])->assertOk();
        $this->assertSame(0, RoomDay::withoutGlobalScopes()->where('room_id', $roomAltro->id)->count());

        $this->azione('applica', ['room_ids' => [$this->room('101')->id], 'azione' => 'assegna', 'staff_id' => $staffAltro->id])->assertStatus(422);
        $this->azione('distribuisci', ['staff_ids' => [$staffAltro->id]])->assertStatus(422);
    }

    public function test_la_modifica_si_vede_sul_telefono_dell_addetta(): void
    {
        $maria = $this->addetta('Maria');
        $this->azione('incolla', ['testo' => '104 101', 'tipo' => 'partenza', 'staff_id' => $maria->id]);
        $this->senzaHotel();

        $this->getJson("/s/{$maria->token_accesso}/camere")->assertOk()->assertJsonCount(2, 'camere')->assertJsonPath('camere.0.numero', '101');
    }
}
