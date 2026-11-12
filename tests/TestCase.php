<?php

namespace Tests;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomDay;
use App\Models\Staff;
use App\Support\CurrentHotel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Crea un hotel con un'addetta e N camere assegnate a lei per oggi. */
    protected function hotelConAddetta(int $camere = 2, array $hotel = []): array
    {
        $h = Hotel::factory()->create($hotel);
        $staff = Staff::factory()->create(['hotel_id' => $h->id]);
        $giorni = collect($camere > 0 ? range(1, $camere) : [])->map(function ($i) use ($h, $staff) {
            $room = Room::factory()->create(['hotel_id' => $h->id, 'numero' => (string) (100 + $i), 'piano' => 1]);

            return RoomDay::factory()->create([
                'hotel_id' => $h->id, 'room_id' => $room->id, 'staff_id' => $staff->id, 'data' => $h->oggi(),
            ]);
        });

        return [$h, $staff, $giorni];
    }

    /** Dimentica l'hotel attivo (come tra una richiesta e l'altra). */
    protected function senzaHotel(): void
    {
        app(CurrentHotel::class)->set(null);
    }
}
