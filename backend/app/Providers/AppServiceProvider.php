<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Enregistrer les commandes Artisan
        $this->commands([
            \App\Console\Commands\MqttListen::class,
            \App\Console\Commands\CheckOfflineDevices::class,
        ]);
    }
}
