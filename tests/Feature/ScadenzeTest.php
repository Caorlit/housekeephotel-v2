<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use App\Notifications\AvvisoSuperadmin;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScadenzeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00'));
        $this->admin = User::factory()->superadmin()->create();
    }

    public function test_nuovo_hotel_con_prova_di_3_mesi_proposta(): void
    {
        $this->actingAs($this->admin)->get('/admin/hotel/nuovo')->assertOk()->assertSee('value="2027-02-15"', false);

        $this->actingAs($this->admin)->post('/admin/hotel', ['nome' => 'Hotel Mare', 'fuso_orario' => 'Europe/Rome', 'attivo' => 1, 'piano' => 'small', 'prova_fino_al' => '2027-02-15']);
        $h = Hotel::where('nome', 'Hotel Mare')->first();
        $this->assertSame('2027-02-15', $h->scadenza()->toDateString());
        $this->assertSame('prova', $h->tipoScadenza());
        $this->assertSame('small', $h->piano);
    }

    public function test_registra_pagamento_sposta_la_scadenza_di_un_anno(): void
    {
        $h = Hotel::factory()->create(['prova_fino_al' => '2026-11-20']);

        $this->actingAs($this->admin)->post("/admin/hotel/{$h->id}/pagamento")->assertSessionHas('ok');
        $this->assertSame('2027-11-20', $h->fresh()->pagato_fino_al->toDateString(), 'Dalla fine della prova, non da oggi');
        $this->assertSame('rinnovo', $h->fresh()->tipoScadenza());

        $this->actingAs($this->admin)->post("/admin/hotel/{$h->id}/pagamento");
        $this->assertSame('2028-11-20', $h->fresh()->pagato_fino_al->toDateString());
    }

    public function test_pagamento_dopo_la_scadenza_parte_da_oggi(): void
    {
        $h = Hotel::factory()->create(['prova_fino_al' => '2026-10-01']);
        $this->actingAs($this->admin)->post("/admin/hotel/{$h->id}/pagamento");
        $this->assertSame('2027-11-15', $h->fresh()->pagato_fino_al->toDateString());
    }

    public function test_preavviso_7_giorni_e_avviso_di_scadenza_una_volta_sola(): void
    {
        $h = Hotel::factory()->create(['nome' => 'Hotel Mare', 'prova_fino_al' => '2026-11-30']);

        $this->artisan('hk:controlla-scadenze');
        Notification::assertNothingSent(); // mancano 15 giorni

        $this->travelTo(CarbonImmutable::parse('2026-11-24 10:00'));
        $this->artisan('hk:controlla-scadenze');
        $this->artisan('hk:controlla-scadenze');
        Notification::assertSentToTimes($this->admin, AvvisoSuperadmin::class, 1);
        Notification::assertSentTo($this->admin, AvvisoSuperadmin::class, fn ($n) => str_contains($n->righe[0], 'La prova gratuita di Hotel Mare scade il 30/11/2026'));

        $this->travelTo(CarbonImmutable::parse('2026-12-01 10:00'));
        $this->artisan('hk:controlla-scadenze');
        $this->artisan('hk:controlla-scadenze');
        Notification::assertSentToTimes($this->admin, AvvisoSuperadmin::class, 2);
        Notification::assertSentTo($this->admin, AvvisoSuperadmin::class, fn ($n) => str_contains($n->righe[0], 'è scaduta il 30/11/2026'));
    }

    public function test_dopo_il_pagamento_si_riparte_con_il_rinnovo(): void
    {
        $h = Hotel::factory()->create(['prova_fino_al' => '2026-11-20', 'pagato_fino_al' => '2027-11-20']);
        $this->artisan('hk:controlla-scadenze');
        Notification::assertNothingSent();

        $this->travelTo(CarbonImmutable::parse('2027-11-14 10:00'));
        $this->artisan('hk:controlla-scadenze');
        Notification::assertSentTo($this->admin, AvvisoSuperadmin::class, fn ($n) => str_contains($n->righe[0], 'Il rinnovo annuale'));
    }

    public function test_elenco_hotel_mostra_la_scadenza(): void
    {
        Hotel::factory()->create(['nome' => 'Hotel Mare', 'prova_fino_al' => '2026-11-18']);
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('18/11/2026')->assertSee('text-amber-700', false);
    }

    public function test_scheda_hotel_con_abbonamento(): void
    {
        $h = Hotel::factory()->create(['prova_fino_al' => '2026-11-10', 'piano' => 'medium']);
        $this->actingAs($this->admin)->get("/admin/hotel/{$h->id}")->assertOk()
            ->assertSee('Registra pagamento annuale')->assertSee('scaduta da 5 giorni')->assertSee('Medium (36–70 camere)');
    }
}
