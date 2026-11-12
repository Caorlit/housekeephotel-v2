<?php

namespace Tests\Feature\Reception;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CamereTest extends TestCase
{
    use RefreshDatabase;

    private function reception(): array
    {
        [$hotel] = $this->hotelConAddetta(0);
        $this->senzaHotel();

        return [$hotel, User::factory()->create(['hotel_id' => $hotel->id])];
    }

    public function test_importazione_rapida_da_101_a_120(): void
    {
        [$hotel, $user] = $this->reception();

        $this->actingAs($user)->post('/reception/camere/importa', ['da' => 101, 'a' => 120, 'piano' => 1])
            ->assertRedirect()->assertSessionHas('ok', 'Aggiunte 20 camere al piano 1.');
        $this->assertSame(20, Room::withoutGlobalScopes()->where('hotel_id', $hotel->id)->where('piano', 1)->count());

        // Ripetere non crea doppioni.
        $this->actingAs($user)->post('/reception/camere/importa', ['da' => 118, 'a' => 122, 'piano' => 1])
            ->assertSessionHas('ok', "Aggiunte 2 camere al piano 1. 3 c'erano già.");
        $this->assertSame(22, Room::withoutGlobalScopes()->where('hotel_id', $hotel->id)->count());
    }

    public function test_importazione_con_intervallo_sbagliato(): void
    {
        [, $user] = $this->reception();

        $this->actingAs($user)->post('/reception/camere/importa', ['da' => 120, 'a' => 101, 'piano' => 1])->assertSessionHasErrors('a');
        $this->actingAs($user)->post('/reception/camere/importa', ['da' => 1, 'a' => 1000, 'piano' => 1])->assertSessionHasErrors('a');
    }

    public function test_numero_unico_per_hotel_ma_uguale_in_hotel_diversi(): void
    {
        [, $userA] = $this->reception();
        [, $userB] = $this->reception();

        $this->actingAs($userA)->post('/reception/camere', ['numero' => '12B', 'piano' => 0])->assertSessionHasNoErrors();
        $this->actingAs($userA)->post('/reception/camere', ['numero' => '12B', 'piano' => 0])->assertSessionHasErrors('numero');
        $this->senzaHotel();
        $this->actingAs($userB)->post('/reception/camere', ['numero' => '12B', 'piano' => 0])->assertSessionHasNoErrors();
    }

    public function test_modifica_e_disattivazione(): void
    {
        [$hotel, $user] = $this->reception();
        $room = Room::factory()->create(['hotel_id' => $hotel->id, 'numero' => '101', 'piano' => 1]);
        $this->senzaHotel();

        $this->actingAs($user)->put("/reception/camere/{$room->id}", ['numero' => '101', 'piano' => 2, 'note_fisse' => 'Vista mare'])
            ->assertSessionHasNoErrors();

        $room->refresh();
        $this->assertSame(2, $room->piano);
        $this->assertSame('Vista mare', $room->note_fisse);
        $this->assertFalse($room->attiva, 'Senza la spunta "attiva" la camera si disattiva');
    }

    public function test_non_si_modificano_le_camere_di_un_altro_hotel(): void
    {
        [, $userA] = $this->reception();
        [$hotelB] = $this->reception();
        $roomB = Room::factory()->create(['hotel_id' => $hotelB->id, 'numero' => '999']);
        $this->senzaHotel();

        $this->actingAs($userA)->put("/reception/camere/{$roomB->id}", ['numero' => 'X', 'piano' => 1])->assertNotFound();
        $this->actingAs($userA)->get('/reception/camere')->assertOk()->assertDontSee('999');
    }
}
