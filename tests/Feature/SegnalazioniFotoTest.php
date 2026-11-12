<?php

namespace Tests\Feature;

use App\Events\IssueCreated;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SegnalazioniFotoTest extends TestCase
{
    use RefreshDatabase;

    private function invia(string $token, array $dati)
    {
        return $this->post("/s/{$token}/segnalazioni", $dati + ['client_action_id' => (string) Str::uuid()], ['Accept' => 'application/json']);
    }

    public function test_la_foto_viene_salvata_in_privato_nella_cartella_dell_hotel(): void
    {
        Storage::fake('local');
        Event::fake([IssueCreated::class]);
        [$hotel, $staff, $giorni] = $this->hotelConAddetta(1);

        $this->invia($staff->token_accesso, [
            'room_day_id' => $giorni[0]->id,
            'nota' => 'Lampada rotta',
            'foto' => UploadedFile::fake()->image('foto.jpg', 1600, 1200),
        ])->assertCreated();

        $issue = Issue::withoutGlobalScopes()->first();
        $this->assertSame('Lampada rotta', $issue->nota);
        $this->assertSame('aperta', $issue->stato);
        $this->assertStringStartsWith("segnalazioni/{$hotel->id}/".now()->format('Y-m').'/', $issue->foto_path);
        Storage::disk('local')->assertExists($issue->foto_path);
        Event::assertDispatched(IssueCreated::class);
    }

    public function test_solo_nota_senza_foto_e_accettata_ma_vuota_no(): void
    {
        Storage::fake('local');
        [, $staff, $giorni] = $this->hotelConAddetta(1);

        $this->invia($staff->token_accesso, ['room_day_id' => $giorni[0]->id, 'nota' => 'Manca un asciugamano'])->assertCreated();
        $this->invia($staff->token_accesso, ['room_day_id' => $giorni[0]->id])->assertUnprocessable();
    }

    public function test_file_che_non_sono_immagini_sono_rifiutati(): void
    {
        Storage::fake('local');
        [, $staff, $giorni] = $this->hotelConAddetta(1);

        $this->invia($staff->token_accesso, [
            'room_day_id' => $giorni[0]->id,
            'foto' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
        ])->assertUnprocessable();

        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_foto_oltre_8_mb_rifiutate(): void
    {
        Storage::fake('local');
        [, $staff, $giorni] = $this->hotelConAddetta(1);

        $this->invia($staff->token_accesso, [
            'room_day_id' => $giorni[0]->id,
            'foto' => UploadedFile::fake()->image('grande.jpg')->size(9000),
        ])->assertUnprocessable();
    }

    public function test_la_reception_dello_stesso_hotel_vede_la_foto_gli_ospiti_no(): void
    {
        Storage::fake('local');
        [$hotel, $staff, $giorni] = $this->hotelConAddetta(1);
        $this->invia($staff->token_accesso, [
            'room_day_id' => $giorni[0]->id, 'foto' => UploadedFile::fake()->image('foto.jpg'),
        ]);
        $issue = Issue::withoutGlobalScopes()->first();
        $this->senzaHotel();

        $this->get("/reception/foto/{$issue->id}")->assertRedirect('/login');

        $reception = User::factory()->create(['hotel_id' => $hotel->id]);
        $this->actingAs($reception)->get("/reception/foto/{$issue->id}")->assertOk();
    }

    public function test_le_foto_piu_vecchie_di_90_giorni_vengono_cancellate(): void
    {
        Storage::fake('local');
        [, $staff, $giorni] = $this->hotelConAddetta(1);
        $this->invia($staff->token_accesso, ['room_day_id' => $giorni[0]->id, 'foto' => UploadedFile::fake()->image('a.jpg')]);
        $vecchia = Issue::withoutGlobalScopes()->first();
        $vecchia->forceFill(['created_at' => now()->subDays(91)])->saveQuietly();
        $this->invia($staff->token_accesso, ['room_day_id' => $giorni[0]->id, 'foto' => UploadedFile::fake()->image('b.jpg')]);
        $recente = Issue::withoutGlobalScopes()->latest('id')->first();
        $pathVecchia = $vecchia->foto_path;
        $this->senzaHotel();

        $this->artisan('hk:cancella-foto')->assertSuccessful();

        Storage::disk('local')->assertMissing($pathVecchia);
        $this->assertNull($vecchia->fresh()->foto_path);
        Storage::disk('local')->assertExists($recente->foto_path);
    }
}
