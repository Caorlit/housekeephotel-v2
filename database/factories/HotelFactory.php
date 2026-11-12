<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HotelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => 'Hotel '.fake()->unique()->lastName(),
            'fuso_orario' => 'Europe/Rome',
            'pin_richiesto' => false,
            'attivo' => true,
        ];
    }
}
