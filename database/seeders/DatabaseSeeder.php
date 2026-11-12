<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea solo il superadmin. Per i dati di prova: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'admin@example.com')],
            ['nome' => 'Superadmin', 'password' => env('SUPERADMIN_PASSWORD', 'cambiami-subito'), 'ruolo' => User::RUOLO_SUPERADMIN, 'hotel_id' => null],
        );
    }
}
