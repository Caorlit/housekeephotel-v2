<?php

namespace Tests\Feature;

use App\Http\Controllers\Reception\FotoController;
use App\Models\Issue;
use App\Models\User;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeri_normalizzati(): void
    {
        $this->assertSame('393471234567', WhatsApp::numero('347 123 4567'));
        $this->assertSame('393471234567', WhatsApp::numero('+39 347-123-4567'));
        $this->assertSame('380501234567', WhatsApp::numero('+380 50 123 4567'));
        $this->assertSame('8801712345678', WhatsApp::numero('00880 1712 345678'));
        $this->assertNull(WhatsApp::numero(''));
        $this->assertSame('https://wa.me/?text=Ciao%20%F0%9F%94%A5', WhatsApp::link('Ciao 🔥'));
    }

    private function scenario(): array
    {
        Storage::fake('local');
        [$hotel, $staff, $giorni] = $this->hotelConAddetta(1, ['nome' => 'Hotel Esperia']);
        $staff->update(['nome' => 'Olena', 'lingua' => 'uk', 'telefono' => '+380 50 123 4567']);
        Storage::disk('local')->put('segnalazioni/x.jpg', 'JPEGDATA');
        $issue = Issue::withoutGlobalScopes()->forceCreate(['hotel_id' => $hotel->id, 'room_day_id' => $giorni[0]->id, 'staff_id' => $staff->id, 'nota' => 'Rubinetto che perde', 'foto_path' => 'segnalazioni/x.jpg', 'stato' => 'aperta']);
        $this->senzaHotel();

        return [$hotel, $staff, $giorni[0], $issue, User::factory()->create(['hotel_id' => $hotel->id])];
    }

    public function test_segnalazione_con_testo_pronto_e_link_alla_foto(): void
    {
        [, , , $issue, $user] = $this->scenario();

        $wa = $this->actingAs($user)->getJson('/reception/dati')->json('segnalazioni.0.whatsapp');
        $testo = rawurldecode(substr($wa, strlen('https://wa.me/?text=')));

        $this->assertStringStartsWith('https://wa.me/?text=', $wa, 'Senza numero: si sceglie il contatto in WhatsApp');
        $this->assertStringContainsString('⚠️ Camera 101 – Hotel Esperia', $testo);
        $this->assertStringContainsString('Rubinetto che perde', $testo);
        $this->assertStringContainsString('Segnalata da Olena', $testo);
        $this->assertStringContainsString('/f/'.$issue->id.'?expires=', $testo);
    }

    public function test_il_link_della_foto_funziona_solo_firmato_e_scade(): void
    {
        [, , , $issue] = $this->scenario();
        $link = FotoController::linkCondiviso($issue);

        $this->get($link)->assertOk();
        $this->get("/f/{$issue->id}")->assertForbidden();
        $this->get($link.'x')->assertForbidden();

        $this->travel(31)->days();
        $this->get($link)->assertForbidden();
        $this->assertNull(FotoController::linkCondiviso($issue->fresh()));
    }

    public function test_link_stabile_il_tabellone_non_cambia_a_vuoto(): void
    {
        [, , , , $user] = $this->scenario();
        $v = $this->actingAs($user)->getJson('/reception/dati')->json('versione');
        $this->travel(10)->seconds();
        $this->actingAs($user)->getJson("/reception/dati?versione={$v}")->assertNoContent();
    }

    public function test_avviso_urgente_nella_lingua_dell_addetta(): void
    {
        [, $staff, , , $user] = $this->scenario();

        $wa = $this->actingAs($user)->getJson('/reception/dati')->json('addette.0.whatsapp');

        $this->assertStringStartsWith('https://wa.me/380501234567?text=', $wa);
        $this->assertSame('🔥 Номер __NUMERO__: терміново!', rawurldecode(explode('?text=', $wa)[1]));
    }

    public function test_telefono_salvato_dalla_pagina_addette(): void
    {
        [, $staff, , , $user] = $this->scenario();

        $this->actingAs($user)->put("/reception/addette/{$staff->id}", ['nome' => 'Olena', 'lingua' => 'uk', 'telefono' => 'abc', 'attivo' => 1])->assertSessionHasErrors('telefono');
        $this->actingAs($user)->put("/reception/addette/{$staff->id}", ['nome' => 'Olena', 'lingua' => 'uk', 'telefono' => '347 123 4567', 'attivo' => 1])->assertSessionHasNoErrors();
        $this->assertSame('347 123 4567', $staff->fresh()->telefono);
    }
}
