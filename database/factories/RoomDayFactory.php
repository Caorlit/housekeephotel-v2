<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'hotel_id' => fn (array $a) => Room::withoutGlobalScopes()->find($a['room_id'])->hotel_id,
            'data' => now('Europe/Rome')->toDateString(),
            'tipo' => 'partenza',
            'stato' => RoomDay::DA_FARE,
            'urgente' => false,
        ];
    }
}
