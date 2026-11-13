<?php

namespace Tests\Feature\Reception;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\Stay;
use App\Models\User;
use App\Support\LettoreCsvPrenotazioni;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportaPrenotazioniTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-09 08:00', 'Europe/Rome'));
        $this->hotel = Hotel::factory()->create();
        $this->user = User::factory()->create(['hotel_id' => $this->hotel->id]);
        foreach (['102', '103', '202', '204', '207', '208'] as $n) {
            Room::factory()->create(['hotel_id' => $this->hotel->id, 'numero' => $n, 'piano' => (int) $n[0]]);
        }
        $this->senzaHotel();
    }

    private function file(?string $contenuto = null): UploadedFile
    {
        $contenuto ??= file_get_contents(base_path('tests/fixtures/prenotazioni-esempio.csv'));

        return UploadedFile::fake()->createWithContent('prenotazioni.csv', $contenuto);
    }

    private function anteprima(?string $contenuto = null, int $ogni = 2)
    {
        return $this->actingAs($this->user)->post('/reception/prenotazioni/importa', ['file' => $this->file($contenuto), 'riassetto_ogni' => $ogni]);
    }

    public function test_lettore_formato_esperia(): void
    {
        $letto = app(LettoreCsvPrenotazioni::class)->leggi(file_get_contents(base_path('tests/fixtures/prenotazioni-esempio.csv')));

        $this->assertNull($letto['errore']);
        $this->assertCount(8, $letto['righe']);
        $r = $letto['righe'][1];
        $this->assertSame(['208', '2026-10-10', '2026-10-11', 'QA9SX5', 2], [$r['camera'], $r['arrivo'], $r['partenza'], $r['riferimento'], $r['pax']]);
        $this->assertSame('102', $letto['righe'][2]['camera'], '"102-3L-B" diventa 102');
        $this->assertArrayNotHasKey('cliente', $r, 'Il nome del cliente non viene letto');
        $this->assertSame(['la partenza non è dopo l\'arrivo'], $letto['righe'][7]['errori']);
    }

    public function test_lettore_virgola_bom_e_windows_1252(): void
    {
        $csv = "\xEF\xBB\xBFCamera,Arrivo,Partenza\n207,2026-10-10,2026-10-12\n";
        $this->assertSame('207', app(LettoreCsvPrenotazioni::class)->leggi($csv)['righe'][0]['camera']);

        $win = mb_convert_encoding("Arrivo;Partenza;Cliente;PMS\n10/10/2026;12/10/2026;Müller Jürgen;207\n", 'Windows-1252', 'UTF-8');
        $this->assertSame('2026-10-12', app(LettoreCsvPrenotazioni::class)->leggi($win)['righe'][0]['partenza']);
    }

    public function test_colonne_mancanti(): void
    {
        $this->anteprima("Nome;Data\nRossi;10/10/2026\n")->assertSessionHasErrors('file');
    }

    public function test_anteprima_e_conferma(): void
    {
        $this->anteprima()->assertOk()
            ->assertSee('Nuova: 6')
            ->assertSee('Scartata: 2')
            ->assertSee('camera 999 non trovata')
            ->assertDontSee('ROSSI MARIO')
            ->assertDontSee('Gialli');

        $this->assertSame(0, Stay::withoutGlobalScopes()->count(), 'L\'anteprima non salva niente');

        $this->actingAs($this->user)->post('/reception/prenotazioni/importa/conferma')
            ->assertRedirect('/reception/prenotazioni')
            ->assertSessionHas('ok', 'Import completato: 6 nuove, 0 aggiornate.');

        $stays = Stay::withoutGlobalScopes()->where('hotel_id', $this->hotel->id)->get();
        $this->assertCount(6, $stays);
        $this->assertTrue($stays->every(fn ($s) => $s->fonte === 'csv' && $s->riassetto_ogni === 2));
        // Stesso codice su due camere: due prenotazioni.
        $this->assertSame(2, $stays->where('riferimento', 'IH9LQ1')->count());
    }

    public function test_reimportare_non_duplica_e_aggiorna_le_date_cambiate(): void
    {
        $this->anteprima();
        $this->actingAs($this->user)->post('/reception/prenotazioni/importa/conferma');

        // Stesso file con la 207 prolungata di 2 notti.
        $csv = str_replace('10/10/2026;11/10/2026;ROSSI MARIO', '10/10/2026;13/10/2026;ROSSI MARIO', file_get_contents(base_path('tests/fixtures/prenotazioni-esempio.csv')));
        $this->anteprima($csv)->assertSee('Aggiornata: 1')->assertSee('Già presente: 5');
        $this->actingAs($this->user)->post('/reception/prenotazioni/importa/conferma')->assertSessionHas('ok', 'Import completato: 0 nuove, 1 aggiornate.');

        $this->assertSame(6, Stay::withoutGlobalScopes()->count());
        $this->assertSame('2026-10-13', Stay::withoutGlobalScopes()->where('riferimento', 'GF7IB3')->first()->partenza->toDateString());
    }

    public function test_sovrapposizione_con_prenotazione_esistente_scartata(): void
    {
        Stay::factory()->create(['hotel_id' => $this->hotel->id, 'room_id' => Room::withoutGlobalScopes()->where('numero', '207')->first()->id, 'arrivo' => '2026-10-09', 'partenza' => '2026-10-12', 'riferimento' => 'ALTRA']);
        $this->senzaHotel();

        $this->anteprima()->assertSee('camera già occupata dal 09/10 al 12/10');
    }

    public function test_conferma_senza_anteprima(): void
    {
        $this->actingAs($this->user)->post('/reception/prenotazioni/importa/conferma')
            ->assertRedirect('/reception/prenotazioni/importa')->assertSessionHasErrors('file');
    }

    public function test_un_file_non_csv_e_rifiutato(): void
    {
        $this->actingAs($this->user)->post('/reception/prenotazioni/importa', [
            'file' => UploadedFile::fake()->image('foto.jpg'), 'riassetto_ogni' => 2,
        ])->assertSessionHasErrors('file');
    }
}
