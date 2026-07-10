<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/password', [AuthController::class, 'updatePassword']);

    // Dashboard
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/devices', [DashboardController::class, 'deviceStatuses']);
    Route::get('/dashboard/alerts-timeline', [DashboardController::class, 'alertsTimeline']);
    Route::get('/dashboard/roi', [DashboardController::class, 'roiCalculator']);

    // Devices
    Route::apiResource('devices', DeviceController::class);
    Route::get('/devices/{device}/metrics', [DeviceController::class, 'metrics']);
    Route::post('/devices/{device}/simulate', [DeviceController::class, 'simulateMqtt']);

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/bulk-acknowledge', [AlertController::class, 'bulkAcknowledge']);
    Route::post('/alerts/bulk-ack', [AlertController::class, 'bulkAcknowledge']); // alias frontend
    Route::get('/alerts/{alert}', [AlertController::class, 'show']);
    Route::post('/alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge']);
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);
    Route::put('/alerts/{alert}', [AlertController::class, 'updateStatus']); // PUT depuis frontend

    // Maintenance
    Route::apiResource('maintenance', MaintenanceController::class)
        ->only(['index', 'store', 'update']);

    // Device Profiles (pour le modal ajout/modif appareil)
    Route::get('/device-profiles', function () {
        return response()->json(\App\Models\DeviceProfile::all());
    });

    // Référentiels (techniciens pour le modal maintenance)
    Route::get('/referentiels/techniciens', function () {
        return response()->json(
            \App\Models\User::where('role', 'technicien')
                ->where('is_active', true)
                ->select('id', 'name', 'role')
                ->orderBy('name')
                ->get()
        );
    });

    // Users
    Route::apiResource('users', UserController::class);

    // ── Broadcasting auth (canaux privés Reverb avec Bearer token) ───────
    Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
        return Broadcast::auth($request);
    });

    // ── Messagerie ────────────────────────────────────────────────────────
    Route::get('/conversations',                           [MessageController::class, 'conversations']);
    Route::post('/conversations',                          [MessageController::class, 'startConversation']);
    Route::get('/conversations/{conversationId}',          [MessageController::class, 'show']);
    Route::get('/conversations/{conversationId}/messages', [MessageController::class, 'messages']);
    Route::post('/conversations/{conversationId}/messages',[MessageController::class, 'send']);
    Route::post('/conversations/{conversationId}/read',    [MessageController::class, 'markRead']);
    Route::delete('/messages/{message}',                   [MessageController::class, 'deleteMessage']);
});
