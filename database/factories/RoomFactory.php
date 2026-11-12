<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        $piano = fake()->numberBetween(1, 4);

        return [
            'hotel_id' => Hotel::factory(),
            'numero' => (string) ($piano * 100 + fake()->unique()->numberBetween(1, 99)),
            'piano' => $piano,
            'attiva' => true,
        ];
    }
}
