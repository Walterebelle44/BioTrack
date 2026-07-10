<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
    return response()->json(['app' => 'BioTrack IoT API', 'version' => '1.0.0']);
});

// Route d'authentification des canaux privés Reverb/Pusher
// Doit être protégée par Sanctum pour valider le token Bearer
Broadcast::routes(['middleware' => ['auth:sanctum']]);
