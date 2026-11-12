<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'nome' => fake()->firstNameFemale(),
            'lingua' => 'it',
            'attivo' => true,
        ];
    }
}
