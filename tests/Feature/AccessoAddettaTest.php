<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use App\Support\StaffPinCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessoAddettaTest extends TestCase
{
    use RefreshDatabase;

    public function test_il_link_personale_apre_la_schermata_nella_lingua_dell_addetta(): void
    {
        [, $staff] = $this->hotelConAddetta(1);
        $staff->update(['nome' => 'Olena', 'lingua' => 'uk']);

        $this->get("/s/{$staff->token_accesso}")
            ->assertOk()
            ->assertSee('Привіт, Olena')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_il_token_ha_64_caratteri_casuali_e_unici(): void
    {
        [, $a] = $this->hotelConAddetta(0);
        [, $b] = $this->hotelConAddetta(0);

        $this->assertSame(64, strlen($a->token_accesso));
        $this->assertNotSame($a->token_accesso, $b->token_accesso);
    }

    public function test_token_sbagliato_o_addetta_disattivata_non_entrano(): void
    {
        [, $staff] = $this->hotelConAddetta(1);

        $this->get('/s/'.str_repeat('x', 64))->assertNotFound()->assertSee('Link non valido');
        $this->get('/s/corto')->assertNotFound();

        Staff::withoutGlobalScopes()->whereKey($staff->id)->update(['attivo' => false]);
        $this->get("/s/{$staff->token_accesso}")->assertNotFound();
    }

    public function test_rigenerare_il_link_invalida_il_vecchio(): void
    {
        [, $staff] = $this->hotelConAddetta(1);
        $vecchio = $staff->token_accesso;

        $staff->rigeneraToken();
        $this->senzaHotel();

        $this->get("/s/{$vecchio}")->assertNotFound();
        $this->get("/s/{$staff->token_accesso}")->assertOk();
    }

    public function test_hotel_disattivato_blocca_le_addette(): void
    {
        [$hotel, $staff] = $this->hotelConAddetta(1);
        $hotel->update(['attivo' => false]);

        $this->get("/s/{$staff->token_accesso}")->assertNotFound();
    }

    public function test_con_il_pin_attivo_serve_il_pin_e_poi_il_dispositivo_e_ricordato(): void
    {
        [, $staff, $giorni] = $this->hotelConAddetta(1, ['pin_richiesto' => true]);
        $staff->impostaPin('1234');
        $t = $staff->token_accesso;

        $this->get("/s/{$t}")->assertOk()->assertSee('PIN')->assertDontSee('hk-config');
        $this->getJson("/s/{$t}/camere")->assertUnauthorized()->assertJsonPath('errore', 'pin_richiesto');

        $this->post("/s/{$t}/pin", ['pin' => '9999'])->assertUnprocessable();

        $risposta = $this->post("/s/{$t}/pin", ['pin' => '1234'])->assertRedirect("/s/{$t}");
        $cookie = collect($risposta->headers->getCookies())->first(fn ($c) => $c->getName() === StaffPinCookie::nome($staff));
        $this->assertNotNull($cookie);
        $this->assertGreaterThan(now()->addDays(89)->getTimestamp(), $cookie->getExpiresTime());

        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())->get("/s/{$t}")->assertOk()->assertSee('hk-config');
    }

    public function test_cambiare_il_pin_invalida_i_dispositivi_ricordati(): void
    {
        [, $staff] = $this->hotelConAddetta(1, ['pin_richiesto' => true]);
        $staff->impostaPin('1234');
        $risposta = $this->post("/s/{$staff->token_accesso}/pin", ['pin' => '1234']);
        $cookie = collect($risposta->headers->getCookies())->first(fn ($c) => $c->getName() === StaffPinCookie::nome($staff));

        Staff::withoutGlobalScopes()->find($staff->id)->impostaPin('5678');

        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())->get("/s/{$staff->token_accesso}")->assertSee('PIN')->assertDontSee('hk-config');
    }

    public function test_troppi_pin_sbagliati_bloccano_per_qualche_minuto(): void
    {
        [, $staff] = $this->hotelConAddetta(1, ['pin_richiesto' => true]);
        $staff->impostaPin('1234');

        foreach (range(1, 5) as $i) {
            $this->post("/s/{$staff->token_accesso}/pin", ['pin' => '0000'])->assertUnprocessable();
        }
        $this->post("/s/{$staff->token_accesso}/pin", ['pin' => '1234'])->assertStatus(429);
    }

    public function test_senza_camere_compare_il_messaggio_semplice(): void
    {
        [, $staff] = $this->hotelConAddetta(0);

        $this->get("/s/{$staff->token_accesso}")->assertOk()->assertSee('Nessuna camera per oggi');
    }

    public function test_manifest_pwa_personale(): void
    {
        [, $staff] = $this->hotelConAddetta(0);

        $this->get("/s/{$staff->token_accesso}/manifest.webmanifest")
            ->assertOk()
            ->assertJsonPath('start_url', "/s/{$staff->token_accesso}")
            ->assertJsonPath('display', 'standalone');
    }

    public function test_login_reception(): void
    {
        [$hotel] = $this->hotelConAddetta(0);
        User::factory()->create(['hotel_id' => $hotel->id, 'email' => 'r@hotel.test', 'password' => 'segreta123']);

        $this->post('/login', ['email' => 'r@hotel.test', 'password' => 'sbagliata'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'r@hotel.test', 'password' => 'segreta123'])->assertRedirect('/reception');
        $this->get('/reception')->assertOk();
    }
}
