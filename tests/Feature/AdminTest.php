<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\AvvisoSuperadmin;
use App\Notifications\InvitoUtente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->admin = User::factory()->superadmin()->create(['nome' => 'Marco']);
    }

    private function datiHotel(array $extra = []): array
    {
        return $extra + ['nome' => 'Hotel Esperia', 'fuso_orario' => 'Europe/Rome', 'attivo' => 1];
    }

    public function test_elenco_e_creazione_hotel_con_avviso(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('Nuovo hotel');

        $this->actingAs($this->admin)->post('/admin/hotel', $this->datiHotel())->assertRedirect();
        $hotel = Hotel::where('nome', 'Hotel Esperia')->firstOrFail();
        $this->assertTrue($hotel->attivo);

        Notification::assertSentTo($this->admin, AvvisoSuperadmin::class, fn ($n) => $n->oggetto === 'nuovo hotel: Hotel Esperia');
        $this->actingAs($this->admin)->get('/admin')->assertSee('Hotel Esperia');
    }

    public function test_nuovo_utente_riceve_l_invito_e_parte_l_avviso(): void
    {
        $hotel = Hotel::factory()->create(['nome' => 'Hotel Esperia']);

        $this->actingAs($this->admin)->post("/admin/hotel/{$hotel->id}/utenti", ['nome' => 'Giulia', 'email' => 'giulia@esperia.it'])
            ->assertSessionHas('ok', fn ($m) => str_contains($m, 'giulia@esperia.it'));

        $giulia = User::where('email', 'giulia@esperia.it')->first();
        $this->assertSame($hotel->id, $giulia->hotel_id);
        $this->assertSame('reception', $giulia->ruolo);
        Notification::assertSentTo($giulia, InvitoUtente::class);
        Notification::assertSentTo($this->admin, AvvisoSuperadmin::class, fn ($n) => $n->oggetto === 'nuovo utente per Hotel Esperia');

        $this->actingAs($this->admin)->post("/admin/utenti/{$giulia->id}/invito")->assertSessionHas('ok');
        Notification::assertSentToTimes($giulia, InvitoUtente::class, 2);
    }

    public function test_email_utente_duplicata(): void
    {
        $hotel = Hotel::factory()->create();
        User::factory()->create(['email' => 'x@y.it']);
        $this->actingAs($this->admin)->post("/admin/hotel/{$hotel->id}/utenti", ['nome' => 'X', 'email' => 'x@y.it'])->assertSessionHasErrors('email');
    }

    public function test_disattivare_utente_e_hotel(): void
    {
        $hotel = Hotel::factory()->create();
        $u = User::factory()->create(['hotel_id' => $hotel->id]);

        $this->actingAs($this->admin)->put("/admin/utenti/{$u->id}", ['nome' => $u->nome, 'email' => $u->email])->assertSessionHasNoErrors();
        $this->assertFalse($u->fresh()->attivo);

        $this->actingAs($this->admin)->put("/admin/hotel/{$hotel->id}", $this->datiHotel(['attivo' => 0]))->assertSessionHasNoErrors();
        $this->assertFalse($hotel->fresh()->attivo);
    }

    public function test_eliminazione_solo_scrivendo_il_nome_e_cancella_tutto(): void
    {
        Storage::fake('local');
        [$hotel, $staff] = $this->hotelConAddetta(2, ['nome' => 'Hotel Demo']);
        $this->senzaHotel();
        Storage::disk('local')->put("segnalazioni/{$hotel->id}/2026-11/a.jpg", 'x');

        $this->actingAs($this->admin)->delete("/admin/hotel/{$hotel->id}", ['conferma' => 'hotel demo'])->assertSessionHasErrors('conferma');
        $this->assertNotNull($hotel->fresh());

        $this->actingAs($this->admin)->delete("/admin/hotel/{$hotel->id}", ['conferma' => 'Hotel Demo'])->assertRedirect('/admin');
        $this->assertNull($hotel->fresh());
        $this->assertSame(0, Room::withoutGlobalScopes()->count());
        $this->assertSame(0, Staff::withoutGlobalScopes()->count());
        Storage::disk('local')->assertMissing("segnalazioni/{$hotel->id}/2026-11/a.jpg");
    }

    public function test_la_reception_non_entra_nell_admin(): void
    {
        $hotel = Hotel::factory()->create();
        $reception = User::factory()->create(['hotel_id' => $hotel->id]);

        $this->actingAs($reception)->get('/admin')->assertForbidden();
        $this->actingAs($reception)->post('/admin/hotel', $this->datiHotel())->assertForbidden();
        $this->actingAs($reception)->post("/admin/hotel/{$hotel->id}/utenti", ['nome' => 'X', 'email' => 'x@x.it'])->assertForbidden();
    }

    public function test_il_superadmin_non_si_modifica_dalla_pagina_utenti(): void
    {
        $altro = User::factory()->superadmin()->create();
        $this->actingAs($this->admin)->put("/admin/utenti/{$altro->id}", ['nome' => 'X', 'email' => 'x@x.it'])->assertNotFound();
    }
}
