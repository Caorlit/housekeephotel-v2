<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\RoomDay;
use App\Models\User;
use App\Services\RoomStateService;
use App\Support\CurrentHotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatoCamereTest extends TestCase
{
    use RefreshDatabase;

    private function azione(string $token, int $roomDayId, string $stato, ?int $ts = null, ?string $id = null)
    {
        return $this->postJson("/s/{$token}/azioni", ['azioni' => [[
            'client_action_id' => $id ?? (string) Str::uuid(),
            'room_day_id' => $roomDayId,
            'stato' => $stato,
            'ts' => $ts ?? now()->getTimestampMs(),
        ]]]);
    }

    public function test_da_fare_in_pulizia_pronta_con_orari_e_registro(): void
    {
        [, $staff, $giorni] = $this->hotelConAddetta(1);
        $rd = $giorni[0];

        $this->azione($staff->token_accesso, $rd->id, 'in_pulizia')->assertOk();
        $rd->refresh();
        $this->assertSame('in_pulizia', $rd->stato);
        $this->assertNotNull($rd->iniziata_alle);
        $this->assertNull($rd->finita_alle);

        $this->azione($staff->token_accesso, $rd->id, 'pronta')->assertOk()
            ->assertJsonPath('camere.0.stato', 'pronta');
        $rd->refresh();
        $this->assertSame('pronta', $rd->stato);
        $this->assertNotNull($rd->finita_alle);

        $this->assertSame(2, EventLog::withoutGlobalScopes()->where('tipo', 'stato_cambiato')->where('staff_id', $staff->id)->count());
    }

    public function test_riaprire_una_camera_pronta_la_rimette_in_pulizia(): void
    {
        [, $staff, $giorni] = $this->hotelConAddetta(1);
        $rd = $giorni[0];
        $t = now()->getTimestampMs();

        $this->azione($staff->token_accesso, $rd->id, 'in_pulizia', $t - 2000);
        $inizio = $rd->fresh()->iniziata_alle;
        $this->azione($staff->token_accesso, $rd->id, 'pronta', $t - 1000);
        $this->azione($staff->token_accesso, $rd->id, 'in_pulizia', $t);

        $rd->refresh();
        $this->assertSame('in_pulizia', $rd->stato);
        $this->assertNull($rd->finita_alle);
        $this->assertEquals($inizio, $rd->iniziata_alle, "L'inizio originale resta");
        $this->assertSame(1, EventLog::withoutGlobalScopes()->where('tipo', 'camera_riaperta')->count());
    }

    public function test_un_azione_offline_piu_vecchia_dell_ultimo_cambio_non_viene_applicata(): void
    {
        [, $staff, $giorni] = $this->hotelConAddetta(1);
        $rd = $giorni[0];
        $adesso = now()->getTimestampMs();

        // La reception (o l'addetta da un altro momento) segna "pronta" adesso...
        $this->azione($staff->token_accesso, $rd->id, 'pronta', $adesso)->assertOk();
        // ...poi arriva in ritardo un'azione fatta offline 10 minuti fa.
        $id = (string) Str::uuid();
        $this->azione($staff->token_accesso, $rd->id, 'in_pulizia', $adesso - 600000, $id)
            ->assertJsonPath("risultati.{$id}", 'superata');

        $this->assertSame('pronta', $rd->fresh()->stato);
        $this->assertSame(1, EventLog::withoutGlobalScopes()->where('tipo', 'azione_superata')->count());
    }

    public function test_un_orologio_del_telefono_avanti_non_blocca_le_azioni_successive(): void
    {
        [, $staff, $giorni] = $this->hotelConAddetta(1);
        $rd = $giorni[0];

        // Telefono con l'orologio avanti di un'ora.
        $this->azione($staff->token_accesso, $rd->id, 'in_pulizia', now()->addHour()->getTimestampMs());
        $this->travel(1)->minutes();
        $this->azione($staff->token_accesso, $rd->id, 'pronta');

        $this->assertSame('pronta', $rd->fresh()->stato);
    }

    public function test_la_reception_puo_cambiare_lo_stato_e_resta_nel_registro(): void
    {
        [$hotel, , $giorni] = $this->hotelConAddetta(1);
        $user = User::factory()->create(['hotel_id' => $hotel->id]);
        app(CurrentHotel::class)->set($hotel);

        $esito = app(RoomStateService::class)->cambiaStato($giorni[0], RoomDay::PRONTA, $user);

        $this->assertSame(RoomStateService::ESITO_APPLICATA, $esito);
        $this->assertSame('pronta', $giorni[0]->fresh()->stato);
        $this->assertSame($user->id, EventLog::first()->user_id);
    }

    public function test_stato_non_valido_rifiutato(): void
    {
        [, $staff, $giorni] = $this->hotelConAddetta(1);
        $this->azione($staff->token_accesso, $giorni[0]->id, 'distrutta')->assertUnprocessable();
    }

    public function test_le_camere_sono_in_ordine_con_le_urgenti_in_cima(): void
    {
        [$hotel, $staff, $giorni] = $this->hotelConAddetta(3);
        RoomDay::withoutGlobalScopes()->whereKey($giorni[2]->id)->update(['urgente' => true]);

        $this->senzaHotel();
        $risposta = $this->getJson("/s/{$staff->token_accesso}/camere")->assertOk();

        $this->assertSame(['103', '101', '102'], array_column($risposta->json('camere'), 'numero'));
    }

    public function test_il_polling_risponde_204_se_non_e_cambiato_niente(): void
    {
        [, $staff] = $this->hotelConAddetta(2);
        $versione = $this->getJson("/s/{$staff->token_accesso}/camere")->json('versione');

        $this->getJson("/s/{$staff->token_accesso}/camere?versione={$versione}")->assertNoContent();
    }
}
