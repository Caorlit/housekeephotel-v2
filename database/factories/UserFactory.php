<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'ruolo' => User::RUOLO_RECEPTION,
        ];
    }

    public function superadmin(): static
    {
        return $this->state(['hotel_id' => null, 'ruolo' => User::RUOLO_SUPERADMIN]);
    }
}
