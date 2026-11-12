<?php

use Illuminate\Support\Facades\Schedule;

// Cron Hostinger ogni minuto: php artisan schedule:run
Schedule::command('hk:cancella-foto')->dailyAt('03:30')->withoutOverlapping();
