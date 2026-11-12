<?php

namespace Tests\Feature\Reception;

use App\Models\EventLog;
use App\Models\Issue;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TabelloneTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        [$hotel, $staff, $giorni] = $this->hotelConAddetta(3);
        $this->senzaHotel();

        return [$hotel, $staff, $giorni, User::factory()->create(['hotel_id' => $hotel->id])];
    }

    public function test_dati_e_204_se_non_cambia_niente(): void
    {
        [, $staff, , $user] = $this->scenario();

        $r = $this->actingAs($user)->getJson('/reception/dati')->assertOk()
            ->assertJsonCount(3, 'camere')
            ->assertJsonPath('camere.0.numero', '101')
            ->assertJsonPath('camere.0.staff', $staff->nome);

        $this->actingAs($user)->getJson('/reception/dati?versione='.$r->json('versione'))->assertNoContent();
    }

    public function test_l_azione_dell_addetta_cambia_la_versione(): void
    {
        [, $staff, $giorni, $user] = $this->scenario();
        $versione = $this->actingAs($user)->getJson('/reception/dati')->json('versione');

        $this->postJson("/s/{$staff->token_accesso}/azioni", ['azioni' => [[
            'client_action_id' => '0b8a3a8e-4e1c-4d8b-9b1a-1a2b3c4d5e6f', 'room_day_id' => $giorni[1]->id, 'stato' => 'pronta', 'ts' => now()->getTimestampMs(),
        ]]])->assertOk();
        $this->senzaHotel();

        $this->actingAs($user)->getJson("/reception/dati?versione={$versione}")->assertOk()
            ->assertJsonPath('camere.1.stato', 'pronta')
            ->assertJsonPath('camere.1.finita', fn ($v) => preg_match('/^\d\d:\d\d$/', $v) === 1);
    }

    public function test_la_reception_cambia_stato_urgenza_nota_e_addetta(): void
    {
        [$hotel, , $giorni, $user] = $this->scenario();
        $olena = Staff::factory()->create(['hotel_id' => $hotel->id, 'nome' => 'Olena']);
        $this->senzaHotel();
        $id = $giorni[0]->id;

        $this->actingAs($user)->postJson("/reception/camera/{$id}/stato", ['stato' => 'pronta'])->assertOk()->assertJsonPath('camere.0.stato', 'pronta');
        $this->actingAs($user)->postJson("/reception/camera/{$id}", ['urgente' => true])->assertOk()->assertJsonPath('camere.0.urgente', true);
        $this->actingAs($user)->postJson("/reception/camera/{$id}", ['nota_reception' => 'Culla'])->assertOk()->assertJsonPath('camere.0.nota', 'Culla');
        $this->actingAs($user)->postJson("/reception/camera/{$id}", ['staff_id' => $olena->id])->assertOk()->assertJsonPath('camere.0.staff', 'Olena');

        $this->assertSame($user->id, EventLog::withoutGlobalScopes()->where('tipo', 'stato_cambiato')->value('user_id'));
        $this->assertSame(3, EventLog::withoutGlobalScopes()->where('tipo', 'camera_modificata')->count());

        // L'addetta vede urgenza e nota sul telefono.
        $this->senzaHotel();
        $this->getJson("/s/{$olena->token_accesso}/camere")->assertJsonPath('camere.0.urgente', true)->assertJsonPath('camere.0.nota', 'Culla');
    }

    public function test_segnalazione_presa_in_carico_e_risolta(): void
    {
        [$hotel, $staff, $giorni, $user] = $this->scenario();
        $issue = Issue::withoutGlobalScopes()->forceCreate([
            'hotel_id' => $hotel->id, 'room_day_id' => $giorni[0]->id, 'staff_id' => $staff->id, 'nota' => 'Lampada', 'stato' => 'aperta',
        ]);

        $this->actingAs($user)->getJson('/reception/dati')->assertJsonPath('segnalazioni.0.nota', 'Lampada')->assertJsonPath('camere.0.segnalazioni', 1);

        $this->actingAs($user)->postJson("/reception/segnalazione/{$issue->id}", ['stato' => 'presa_in_carico'])->assertOk()
            ->assertJsonPath('segnalazioni.0.stato', 'presa_in_carico');
        $this->assertNotNull($issue->fresh()->letta_alle);

        $this->actingAs($user)->postJson("/reception/segnalazione/{$issue->id}", ['stato' => 'risolta'])->assertOk()
            ->assertJsonCount(0, 'segnalazioni')
            ->assertJsonPath('camere.0.segnalazioni', 0);
        $this->assertNotNull($issue->fresh()->risolta_alle);
    }

    public function test_non_si_toccano_camere_e_segnalazioni_di_un_altro_hotel(): void
    {
        [, , , $userA] = $this->scenario();
        [$hotelB, $staffB, $giorniB] = $this->scenario();
        $issueB = Issue::withoutGlobalScopes()->forceCreate(['hotel_id' => $hotelB->id, 'room_day_id' => $giorniB[0]->id, 'staff_id' => $staffB->id, 'stato' => 'aperta']);
        $this->senzaHotel();

        $this->actingAs($userA)->postJson("/reception/camera/{$giorniB[0]->id}/stato", ['stato' => 'pronta'])->assertNotFound();
        $this->actingAs($userA)->postJson("/reception/camera/{$giorniB[0]->id}", ['urgente' => true])->assertNotFound();
        $this->actingAs($userA)->postJson("/reception/segnalazione/{$issueB->id}", ['stato' => 'risolta'])->assertNotFound();
        $this->actingAs($userA)->getJson('/reception/dati')->assertJsonCount(3, 'camere')->assertJsonCount(0, 'segnalazioni');

        // Assegnare a un'addetta di un altro hotel: rifiutato.
        [, , $giorniA] = [null, null, RoomDay::withoutGlobalScopes()->where('hotel_id', $userA->hotel_id)->get()];
        $this->actingAs($userA)->postJson("/reception/camera/{$giorniA[0]->id}", ['staff_id' => $staffB->id])->assertStatus(422);

        $this->assertSame('da_fare', RoomDay::withoutGlobalScopes()->find($giorniB[0]->id)->stato);
        $this->assertSame('aperta', Issue::withoutGlobalScopes()->find($issueB->id)->stato);
    }

    public function test_senza_login_niente_dati(): void
    {
        $this->getJson('/reception/dati')->assertUnauthorized();
    }
}
