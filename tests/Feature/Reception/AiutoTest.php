<?php

namespace Tests\Feature\Reception;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiutoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_aiuto_e_link_dalle_pagine(): void
    {
        $user = User::factory()->create(['hotel_id' => Hotel::factory()->create()->id]);

        $this->actingAs($user)->get('/reception/aiuto')->assertOk()
            ->assertSee('Come iniziare')->assertSee('Sostituisci (giorno libero)')->assertSee('Domande frequenti');

        foreach (['camere', 'addette', 'giornata', 'prenotazioni'] as $pagina) {
            $this->actingAs($user)->get("/reception/{$pagina}")->assertOk()->assertSee("/reception/aiuto#{$pagina}", false);
        }
        $this->actingAs($user)->get('/reception')->assertSee('/reception/aiuto#tabellone', false);
    }

    public function test_solo_utenti_collegati(): void
    {
        $this->get('/reception/aiuto')->assertRedirect('/login');
    }
}
