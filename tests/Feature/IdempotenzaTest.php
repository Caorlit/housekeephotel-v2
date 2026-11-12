<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdempotenzaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_stessa_azione_inviata_due_volte_si_applica_una_volta_sola(): void
    {
        [, $staff, $giorni] = $this->hotelConAddetta(1);
        $id = (string) Str::uuid();
        $corpo = ['azioni' => [[
            'client_action_id' => $id, 'room_day_id' => $giorni[0]->id, 'stato' => 'in_pulizia', 'ts' => now()->getTimestampMs(),
        ]]];

        $this->postJson("/s/{$staff->token_accesso}/azioni", $corpo)->assertJsonPath("risultati.{$id}", 'applicata');
        $this->postJson("/s/{$staff->token_accesso}/azioni", $corpo)->assertJsonPath("risultati.{$id}", 'duplicata');

        $this->assertSame(1, EventLog::withoutGlobalScopes()->count());
    }

    public function test_una_coda_offline_reinviata_dopo_un_errore_di_rete_non_duplica(): void
    {
        [, $staff, $giorni] = $this->hotelConAddetta(2);
        $t = now()->getTimestampMs();
        $coda = [
            ['client_action_id' => (string) Str::uuid(), 'room_day_id' => $giorni[0]->id, 'stato' => 'in_pulizia', 'ts' => $t - 3000],
            ['client_action_id' => (string) Str::uuid(), 'room_day_id' => $giorni[0]->id, 'stato' => 'pronta', 'ts' => $t - 2000],
            ['client_action_id' => (string) Str::uuid(), 'room_day_id' => $giorni[1]->id, 'stato' => 'in_pulizia', 'ts' => $t - 1000],
        ];

        // Il server ha ricevuto e salvato, ma la risposta si è persa: il telefono reinvia tutto.
        $this->postJson("/s/{$staff->token_accesso}/azioni", ['azioni' => $coda])->assertOk();
        $this->postJson("/s/{$staff->token_accesso}/azioni", ['azioni' => $coda])->assertOk();

        $this->assertSame(3, EventLog::withoutGlobalScopes()->count());
        $this->assertSame('pronta', $giorni[0]->fresh()->stato);
        $this->assertSame('in_pulizia', $giorni[1]->fresh()->stato);
    }

    public function test_la_stessa_segnalazione_reinviata_non_crea_doppioni(): void
    {
        Storage::fake('local');
        [, $staff, $giorni] = $this->hotelConAddetta(1);
        $dati = fn () => [
            'client_action_id' => '7b1f4c1e-5d2a-4a8e-9a63-0c1e2f3a4b5c',
            'room_day_id' => $giorni[0]->id,
            'foto' => UploadedFile::fake()->image('foto.jpg', 1600, 1200),
        ];

        $this->post("/s/{$staff->token_accesso}/segnalazioni", $dati(), ['Accept' => 'application/json'])->assertCreated();
        $this->post("/s/{$staff->token_accesso}/segnalazioni", $dati(), ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('esito', 'duplicata');

        $this->assertSame(1, Issue::withoutGlobalScopes()->count());
        $this->assertCount(1, Storage::disk('local')->allFiles('segnalazioni'));
    }
}
