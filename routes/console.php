<?php

use Illuminate\Support\Facades\Schedule;

// Cron Hostinger ogni minuto: php artisan schedule:run
Schedule::command('hk:cancella-foto')->dailyAt('03:30')->withoutOverlapping();

// Le camere di oggi nascono dalle prenotazioni anche se nessuno apre il gestionale.
Schedule::command('hk:genera-giornate')->hourly()->withoutOverlapping();

// Avvisi ai superadmin (ora italiana).
Schedule::command('hk:riepilogo-settimanale')->weeklyOn(1, '7:13')->timezone('Europe/Rome');
Schedule::command('hk:controlla-inattivita')->dailyAt('9:17')->timezone('Europe/Rome');
