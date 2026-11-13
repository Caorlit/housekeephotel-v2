<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class StayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'hotel_id' => fn (array $a) => Room::withoutGlobalScopes()->find($a['room_id'])->hotel_id,
            'arrivo' => now()->toDateString(),
            'partenza' => now()->addDays(3)->toDateString(),
            'riassetto_ogni' => 2,
            'fonte' => 'manuale',
        ];
    }
}
