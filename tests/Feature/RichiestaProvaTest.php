<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\TrialRequest;
use App\Models\User;
use App\Notifications\AvvisoSuperadmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RichiestaProvaTest extends TestCase
{
    use RefreshDatabase;

    private function dati(array $extra = []): array
    {
        return $extra + ['nome' => 'Anna Rossi', 'hotel' => 'Hotel Mare', 'citta' => 'Caorle', 'email' => 'anna@hotelmare.it', 'camere' => 40, 'privacy' => '1', 'lingua' => 'it'];
    }

    public function test_il_modulo_del_sito_salva_avvisa_e_torna_alla_pagina_grazie(): void
    {
        Notification::fake();
        $admin = User::factory()->superadmin()->create();

        $this->post('/richiesta-prova', $this->dati())->assertRedirect('https://housekeephotel.com/grazie/');
        $this->post('/richiesta-prova', $this->dati(['lingua' => 'es', 'hotel' => 'Hotel Sol']))->assertRedirect('https://housekeephotel.com/es/grazie/');

        $this->assertSame(2, TrialRequest::count());
        Notification::assertSentTo($admin, AvvisoSuperadmin::class, fn ($n) => $n->oggetto === 'richiesta di prova: Hotel Mare');
    }

    public function test_senza_consenso_privacy_o_email_sbagliata_torna_al_modulo(): void
    {
        $this->post('/richiesta-prova', $this->dati(['privacy' => null]))->assertRedirect('https://housekeephotel.com/?errore=1#prova');
        $this->post('/richiesta-prova', $this->dati(['email' => 'non-una-email', 'lingua' => 'en']))->assertRedirect('https://housekeephotel.com/en/?errore=1#prova');
        $this->assertSame(0, TrialRequest::count());
    }

    public function test_i_robot_vengono_ignorati_senza_saperlo(): void
    {
        Notification::fake();
        $this->post('/richiesta-prova', $this->dati(['sito_web' => 'http://spam.example']))->assertRedirect('https://housekeephotel.com/grazie/');
        $this->assertSame(0, TrialRequest::count());
        Notification::assertNothingSent();
    }

    public function test_limite_di_invii(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/richiesta-prova', $this->dati(['email' => "a{$i}@x.it"]));
        }
        $this->post('/richiesta-prova', $this->dati())->assertStatus(429);
    }

    public function test_pagina_richieste_e_creazione_hotel_precompilata(): void
    {
        Notification::fake();
        $admin = User::factory()->superadmin()->create();
        $r = TrialRequest::create($this->dati());

        $this->actingAs($admin)->get('/admin/richieste')->assertOk()->assertSee('Hotel Mare')->assertSee('anna@hotelmare.it');
        $this->actingAs($admin)->get("/admin/hotel/nuovo?nome=Hotel+Mare&email=anna@hotelmare.it&richiesta={$r->id}")
            ->assertOk()->assertSee('value="Hotel Mare"', false);

        $this->actingAs($admin)->post('/admin/hotel', ['nome' => 'Hotel Mare', 'fuso_orario' => 'Europe/Rome', 'attivo' => 1, 'richiesta' => $r->id]);
        $this->assertSame('attivata', $r->fresh()->stato);
        $this->assertSame(Hotel::where('nome', 'Hotel Mare')->value('id'), $r->fresh()->hotel_id);
    }

    public function test_la_reception_non_vede_le_richieste(): void
    {
        $u = User::factory()->create(['hotel_id' => Hotel::factory()->create()->id]);
        $this->actingAs($u)->get('/admin/richieste')->assertForbidden();
    }
}
