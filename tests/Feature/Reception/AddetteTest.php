<?php

namespace Tests\Feature\Reception;

use App\Models\Hotel;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AddetteTest extends TestCase
{
    use RefreshDatabase;

    private function reception(): array
    {
        [$hotel, $staff] = $this->hotelConAddetta(0);
        $this->senzaHotel();

        return [$hotel, $staff, User::factory()->create(['hotel_id' => $hotel->id])];
    }

    public function test_aggiunta_con_lingua_e_token_casuale(): void
    {
        [$hotel, , $user] = $this->reception();

        $this->actingAs($user)->post('/reception/addette', ['nome' => 'Olena', 'lingua' => 'uk'])->assertSessionHasNoErrors();

        $olena = Staff::withoutGlobalScopes()->where('nome', 'Olena')->first();
        $this->assertSame($hotel->id, $olena->hotel_id);
        $this->assertSame('uk', $olena->lingua);
        $this->assertSame(64, strlen($olena->token_accesso));
    }

    public function test_lingua_non_prevista_rifiutata(): void
    {
        [, , $user] = $this->reception();
        $this->actingAs($user)->post('/reception/addette', ['nome' => 'X', 'lingua' => 'de'])->assertSessionHasErrors('lingua');
    }

    public function test_la_pagina_mostra_link_e_stampa_nella_lingua_dell_addetta(): void
    {
        [, $staff, $user] = $this->reception();
        $staff->update(['lingua' => 'ro', 'nome' => 'Ioana']);
        $this->senzaHotel();

        $this->actingAs($user)->get('/reception/addette')->assertOk()->assertSee($staff->token_accesso);
        $this->senzaHotel();
        $this->actingAs($user)->get("/reception/addette/{$staff->id}/stampa")
            ->assertOk()
            ->assertSee('Bună, Ioana')
            ->assertSee('Apasă butonul roșu', false)
            ->assertSee($staff->token_accesso);
    }

    public function test_rigenera_link(): void
    {
        [, $staff, $user] = $this->reception();
        $vecchio = $staff->token_accesso;

        $this->actingAs($user)->post("/reception/addette/{$staff->id}/rigenera")->assertSessionHas('ok');

        $this->assertNotSame($vecchio, $staff->fresh()->token_accesso);
        $this->get("/s/{$vecchio}")->assertNotFound();
    }

    public function test_disattivare_blocca_il_link(): void
    {
        [, $staff, $user] = $this->reception();

        $this->actingAs($user)->put("/reception/addette/{$staff->id}", ['nome' => $staff->nome, 'lingua' => 'it'])->assertSessionHasNoErrors();
        $this->senzaHotel();

        $this->assertFalse($staff->fresh()->attivo);
        $this->get("/s/{$staff->token_accesso}")->assertNotFound();
    }

    public function test_pin_impostato_tolto_e_interruttore_hotel(): void
    {
        [$hotel, $staff, $user] = $this->reception();

        $this->actingAs($user)->post("/reception/addette/{$staff->id}/pin", ['pin' => '12a4'])->assertSessionHasErrors('pin');
        $this->actingAs($user)->post("/reception/addette/{$staff->id}/pin", ['pin' => '4321'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('4321', $staff->fresh()->pin));

        $this->actingAs($user)->post('/reception/addette/pin-hotel', ['pin_richiesto' => '1']);
        $this->assertTrue(Hotel::find($hotel->id)->pin_richiesto);
        $this->senzaHotel();
        $this->get("/s/{$staff->token_accesso}")->assertSee('PIN')->assertDontSee('hk-config');

        $this->actingAs($user)->post("/reception/addette/{$staff->id}/pin", ['pin' => ''])->assertSessionHasNoErrors();
        $this->assertNull($staff->fresh()->pin);
    }

    public function test_non_si_toccano_le_addette_di_un_altro_hotel(): void
    {
        [, , $userA] = $this->reception();
        [, $staffB] = $this->reception();
        $vecchio = $staffB->token_accesso;
        $this->senzaHotel();

        $this->actingAs($userA)->post("/reception/addette/{$staffB->id}/rigenera")->assertNotFound();
        $this->actingAs($userA)->get("/reception/addette/{$staffB->id}/stampa")->assertNotFound();
        $this->actingAs($userA)->get('/reception/addette')->assertDontSee($vecchio);
        $this->assertSame($vecchio, $staffB->fresh()->token_accesso);
    }
}
