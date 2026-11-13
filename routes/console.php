<?php

use Illuminate\Support\Facades\Schedule;

// Cron Hostinger ogni minuto: php artisan schedule:run
Schedule::command('hk:cancella-foto')->dailyAt('03:30')->withoutOverlapping();

// Le camere di oggi nascono dalle prenotazioni anche se nessuno apre il gestionale.
Schedule::command('hk:genera-giornate')->hourly()->withoutOverlapping();
