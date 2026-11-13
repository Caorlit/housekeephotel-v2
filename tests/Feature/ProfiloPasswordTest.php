<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use App\Notifications\AvvisoSuperadmin;
use App\Notifications\InvitoUtente;
use App\Notifications\ReimpostaPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfiloPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function reception(): User
    {
        return User::factory()->create(['hotel_id' => Hotel::factory()->create()->id, 'email' => 'r@hotel.test', 'password' => 'vecchia-password']);
    }

    public function test_profilo_cambio_nome_senza_password(): void
    {
        $user = $this->reception();
        $this->actingAs($user)->put('/profilo', ['nome' => 'Giulia', 'email' => 'r@hotel.test'])->assertSessionHasNoErrors();
        $this->assertSame('Giulia', $user->fresh()->nome);
    }

    public function test_cambio_email_e_password_richiedono_la_password_attuale(): void
    {
        $user = $this->reception();

        $this->actingAs($user)->put('/profilo', ['nome' => 'X', 'email' => 'nuova@hotel.test', 'password_attuale' => 'sbagliata'])
            ->assertSessionHasErrors('password_attuale');
        $this->assertSame('r@hotel.test', $user->fresh()->email);

        $this->actingAs($user)->put('/profilo', [
            'nome' => 'X', 'email' => 'nuova@hotel.test', 'password_attuale' => 'vecchia-password',
            'password' => 'nuova-password-123', 'password_confirmation' => 'nuova-password-123',
        ])->assertSessionHasNoErrors();

        $this->assertSame('nuova@hotel.test', $user->fresh()->email);
        $this->assertTrue(Hash::check('nuova-password-123', $user->fresh()->password));
    }

    public function test_password_troppo_corta(): void
    {
        $user = $this->reception();
        $this->actingAs($user)->put('/profilo', ['nome' => 'X', 'email' => 'r@hotel.test', 'password_attuale' => 'vecchia-password', 'password' => 'corta', 'password_confirmation' => 'corta'])
            ->assertSessionHasErrors('password');
    }

    public function test_password_dimenticata_manda_il_link_e_si_puo_reimpostare(): void
    {
        Notification::fake();
        $user = $this->reception();

        $this->post('/password/dimenticata', ['email' => 'r@hotel.test'])->assertSessionHas('ok');
        $this->post('/password/dimenticata', ['email' => 'nessuno@hotel.test'])->assertSessionHas('ok'); // stessa risposta

        $token = null;
        Notification::assertSentTo($user, ReimpostaPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get("/password/reimposta/{$token}?email=r@hotel.test")->assertOk()->assertSee('Nuova password');
        $this->post('/password/reimposta', ['token' => $token, 'email' => 'r@hotel.test', 'password' => 'scelta-nuova-123', 'password_confirmation' => 'scelta-nuova-123'])
            ->assertRedirect('/reception');
        $this->assertTrue(Hash::check('scelta-nuova-123', $user->fresh()->password));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_link_sbagliato(): void
    {
        $this->reception();
        $this->post('/password/reimposta', ['token' => 'falso', 'email' => 'r@hotel.test', 'password' => 'scelta-nuova-123', 'password_confirmation' => 'scelta-nuova-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_invito_vale_7_giorni(): void
    {
        Notification::fake();
        $user = $this->reception();
        $user->inviaInvito();

        $token = null;
        Notification::assertSentTo($user, InvitoUtente::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->travel(6)->days();
        $this->post('/password/reimposta', ['token' => $token, 'email' => 'r@hotel.test', 'invito' => 1, 'password' => 'benvenuto-1234', 'password_confirmation' => 'benvenuto-1234'])
            ->assertRedirect('/reception');
    }

    public function test_invito_scaduto_dopo_7_giorni(): void
    {
        Notification::fake();
        $user = $this->reception();
        $user->inviaInvito();
        $token = Notification::sent($user, InvitoUtente::class)->first()->token;

        $this->travel(8)->days();
        $this->post('/password/reimposta', ['token' => $token, 'email' => 'r@hotel.test', 'invito' => 1, 'password' => 'benvenuto-1234', 'password_confirmation' => 'benvenuto-1234'])
            ->assertSessionHasErrors('email');
    }

    public function test_utente_disattivato_non_entra_ed_e_buttato_fuori(): void
    {
        $user = $this->reception();
        $user->update(['attivo' => false]);

        $this->post('/login', ['email' => 'r@hotel.test', 'password' => 'vecchia-password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get('/reception')->assertRedirect('/login');
    }

    public function test_comando_email_di_prova(): void
    {
        Notification::fake();
        $admin = User::factory()->superadmin()->create();

        $this->artisan('hk:prova-email')->assertSuccessful();
        Notification::assertSentTo($admin, AvvisoSuperadmin::class);
    }

    public function test_le_email_sono_in_italiano(): void
    {
        $user = $this->reception();
        $html = (string) (new InvitoUtente('abc'))->toMail($user)->render();

        $this->assertStringContainsString('Scegli la password', $html);
        $this->assertStringContainsString('copia e incolla questo indirizzo', $html);
        $this->assertStringNotContainsString('Regards', $html);
    }
}
