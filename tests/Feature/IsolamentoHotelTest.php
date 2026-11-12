<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\RoomDay;
use App\Models\User;
use App\Support\CurrentHotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class IsolamentoHotelTest extends TestCase
{
    use RefreshDatabase;

    public function test_il_filtro_automatico_mostra_solo_i_dati_dell_hotel_attivo(): void
    {
        [$a] = $this->hotelConAddetta(3);
        [$b] = $this->hotelConAddetta(2);

        app(CurrentHotel::class)->set($a);
        $this->assertSame(3, RoomDay::count());
        $this->assertTrue(RoomDay::all()->every(fn ($rd) => $rd->hotel_id === $a->id));

        app(CurrentHotel::class)->set($b);
        $this->assertSame(2, RoomDay::count());
    }

    public function test_non_si_puo_creare_un_record_per_un_altro_hotel(): void
    {
        [$a] = $this->hotelConAddetta(1);
        [$b, , $giorniB] = $this->hotelConAddetta(1);

        app(CurrentHotel::class)->set($a);
        $this->expectException(\LogicException::class);
        Issue::create(['hotel_id' => $b->id, 'room_day_id' => $giorniB[0]->id]);
    }

    public function test_un_addetta_non_puo_cambiare_le_camere_di_un_altro_hotel(): void
    {
        [, $staffA] = $this->hotelConAddetta(1);
        [, , $giorniB] = $this->hotelConAddetta(1);
        $this->senzaHotel();

        $id = (string) Str::uuid();
        $this->postJson("/s/{$staffA->token_accesso}/azioni", ['azioni' => [[
            'client_action_id' => $id, 'room_day_id' => $giorniB[0]->id, 'stato' => 'pronta', 'ts' => now()->getTimestampMs(),
        ]]])->assertOk()->assertJsonPath("risultati.{$id}", 'non_trovata');

        $this->assertSame('da_fare', RoomDay::withoutGlobalScopes()->find($giorniB[0]->id)->stato);
    }

    public function test_un_addetta_non_puo_segnalare_su_camere_di_un_altro_hotel(): void
    {
        [, $staffA] = $this->hotelConAddetta(1);
        [, , $giorniB] = $this->hotelConAddetta(1);
        $this->senzaHotel();

        $this->postJson("/s/{$staffA->token_accesso}/segnalazioni", [
            'client_action_id' => (string) Str::uuid(), 'room_day_id' => $giorniB[0]->id, 'nota' => 'prova',
        ])->assertNotFound();

        $this->assertSame(0, Issue::withoutGlobalScopes()->count());
    }

    public function test_la_reception_vede_solo_le_camere_e_le_foto_del_suo_hotel(): void
    {
        Storage::fake('local');
        [$a, , $giorniA] = $this->hotelConAddetta(1);
        [$b, $staffB, $giorniB] = $this->hotelConAddetta(1);
        $this->senzaHotel();

        RoomDay::withoutGlobalScopes()->whereKey($giorniB[0]->id)->first()->room()->withoutGlobalScopes()->update(['numero' => 'B-999']);
        Storage::disk('local')->put('segnalazioni/b.jpg', 'x');
        $issueB = Issue::withoutGlobalScopes()->forceCreate([
            'hotel_id' => $b->id, 'room_day_id' => $giorniB[0]->id, 'staff_id' => $staffB->id, 'foto_path' => 'segnalazioni/b.jpg', 'stato' => 'aperta',
        ]);

        $receptionA = User::factory()->create(['hotel_id' => $a->id]);

        $this->actingAs($receptionA)->get('/reception')->assertOk()->assertDontSee('B-999');
        $this->senzaHotel();
        $this->actingAs($receptionA)->get("/reception/foto/{$issueB->id}")->assertNotFound();
    }

    public function test_la_reception_non_accede_all_admin_e_viceversa(): void
    {
        [$a] = $this->hotelConAddetta(1);
        $reception = User::factory()->create(['hotel_id' => $a->id]);
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($reception)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/reception')->assertForbidden();
    }
}
