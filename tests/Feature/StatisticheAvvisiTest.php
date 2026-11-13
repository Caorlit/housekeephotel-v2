<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\Hotel;
use App\Models\Issue;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\AvvisoSuperadmin;
use App\Services\Statistiche;
use App\Support\Avvisi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StatisticheAvvisiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-16 08:00', 'Europe/Rome')); // lunedì
        $this->admin = User::factory()->superadmin()->create();
        $this->hotel = Hotel::factory()->create(['nome' => 'Hotel Esperia', 'created_at' => now()->subMonths(2)]);
    }

    /** Camera pulita da $staff il $giorno in $minuti. */
    private function pulita(string $numero, string $giorno, string $tipo, int $minuti, ?Staff $staff = null): RoomDay
    {
        $room = Room::withoutGlobalScopes()->firstOrCreate(['hotel_id' => $this->hotel->id, 'numero' => $numero], ['piano' => 1]);
        $inizio = CarbonImmutable::parse("{$giorno} 09:00", 'Europe/Rome');

        return RoomDay::withoutGlobalScopes()->forceCreate([
            'hotel_id' => $this->hotel->id, 'room_id' => $room->id, 'data' => $giorno, 'tipo' => $tipo, 'stato' => 'pronta',
            'staff_id' => $staff?->id, 'iniziata_alle' => $inizio, 'finita_alle' => $inizio->addMinutes($minuti),
        ]);
    }

    private function evento($quando): void
    {
        EventLog::withoutTimestamps(fn () => EventLog::withoutGlobalScopes()->forceCreate([
            'hotel_id' => $this->hotel->id, 'tipo' => 'stato_cambiato', 'created_at' => $quando,
        ]));
    }

    public function test_statistiche_medie_per_tipo_e_per_addetta_senza_valori_anomali(): void
    {
        $maria = Staff::factory()->create(['hotel_id' => $this->hotel->id, 'nome' => 'Maria']);
        $olena = Staff::factory()->create(['hotel_id' => $this->hotel->id, 'nome' => 'Olena']);
        $this->pulita('101', '2026-11-10', 'partenza', 30, $maria);
        $this->pulita('102', '2026-11-10', 'partenza', 40, $maria);
        $this->pulita('103', '2026-11-11', 'fermata', 15, $olena);
        $this->pulita('104', '2026-11-11', 'fermata', 600, $olena); // tocco dimenticato: escluso dalla media
        $this->pulita('105', '2026-10-01', 'partenza', 99, $maria);  // fuori periodo
        $rd = $this->pulita('106', '2026-11-12', 'fermata', 25, $olena);
        Issue::withoutGlobalScopes()->forceCreate(['hotel_id' => $this->hotel->id, 'room_day_id' => $rd->id, 'stato' => 'risolta',
            'created_at' => CarbonImmutable::parse('2026-11-12 10:00', 'Europe/Rome'), 'risolta_alle' => CarbonImmutable::parse('2026-11-12 13:00', 'Europe/Rome')]);

        $s = app(Statistiche::class)->calcola($this->hotel, '2026-11-09', '2026-11-15');

        $this->assertSame(5, $s['pulite']);
        $this->assertSame(35, $s['media_cambio']);
        $this->assertSame(20, $s['media_riassetto'], 'media di 15 e 25: il 600 è escluso');
        $this->assertSame(1, $s['segnalazioni_risolte']);
        $this->assertEquals(3.0, $s['ore_risoluzione']);
        $this->assertSame('Olena', $s['per_addetta'][0]['nome']);
        $this->assertSame(3, $s['per_addetta'][0]['pulite']);
    }

    public function test_pagina_statistiche(): void
    {
        $this->pulita('101', '2026-11-10', 'partenza', 30);
        $this->actingAs($this->admin)->get("/admin/statistiche?hotel={$this->hotel->id}&dal=2026-11-09&al=2026-11-15")
            ->assertOk()->assertSee('30 min')->assertSee('Hotel Esperia');
    }

    public function test_riepilogo_settimanale(): void
    {
        $this->pulita('101', '2026-11-10', 'partenza', 30);

        $this->artisan('hk:riepilogo-settimanale')->assertSuccessful();

        Notification::assertSentTo($this->admin, AvvisoSuperadmin::class, fn ($n) => $n->oggetto === 'riepilogo della settimana'
            && str_contains($n->righe[0], 'Hotel Esperia: 1 camere pulite (1 cambi, 0 riassetti), tempo medio cambio 30 min'));
    }

    public function test_avviso_inattivita_una_volta_sola_e_di_nuovo_dopo_una_ripresa(): void
    {
        $this->evento(now()->subDays(8));

        $this->artisan('hk:controlla-inattivita')->assertSuccessful();
        $this->artisan('hk:controlla-inattivita')->assertSuccessful();
        Notification::assertSentToTimes($this->admin, AvvisoSuperadmin::class, 1);

        // L'hotel riprende a usare l'app, poi si ferma di nuovo per 8 giorni: nuovo avviso.
        $this->evento(now());
        $this->travel(8)->days();
        $this->artisan('hk:controlla-inattivita');
        Notification::assertSentToTimes($this->admin, AvvisoSuperadmin::class, 2);
    }

    public function test_nessun_avviso_se_l_hotel_lavora(): void
    {
        $this->evento(now()->subDays(2));
        $this->artisan('hk:controlla-inattivita');
        Notification::assertNothingSent();
    }

    public function test_avviso_errori_con_limite_per_lo_stesso_errore(): void
    {
        $e = new \RuntimeException('Database non raggiungibile');
        Avvisi::errore($e);
        Avvisi::errore($e);
        Avvisi::errore(new \LogicException('Altro errore'));

        Notification::assertSentToTimes($this->admin, AvvisoSuperadmin::class, 2);
        Notification::assertSentTo($this->admin, AvvisoSuperadmin::class, fn ($n) => $n->errore && str_contains($n->righe[0], 'Database non raggiungibile'));
    }

    public function test_un_problema_di_invio_email_non_blocca_niente(): void
    {
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP giù'));
        $this->assertFalse(Avvisi::superadmin('prova', ['x']));
    }
}
