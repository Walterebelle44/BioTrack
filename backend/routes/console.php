<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vérification périodique des appareils offline (toutes les 5 minutes)
Schedule::command('mqtt:check-offline')->everyFiveMinutes();
