<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\MqttService;
use Illuminate\Console\Command;

class CheckOfflineDevices extends Command
{
    protected $signature = 'mqtt:check-offline {--threshold=5 : Minutes sans données avant considérer offline}';
    protected $description = 'Vérifie et marque les appareils inactifs comme hors ligne';

    public function __construct(private MqttService $mqttService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $thresholdMinutes = (int) $this->option('threshold');
        $cutoff = now()->subMinutes($thresholdMinutes);

        $devices = Device::where('is_active', true)
            ->where('status', 'online')
            ->where(function ($q) use ($cutoff) {
                $q->where('last_seen_at', '<', $cutoff)
                  ->orWhereNull('last_seen_at');
            })
            ->get();

        foreach ($devices as $device) {
            $this->warn("Appareil inactif: {$device->serial_number} ({$device->name})");
            $this->mqttService->handleDeviceOffline($device->serial_number);
        }

        if ($devices->isEmpty()) {
            $this->info('✅ Tous les appareils sont actifs.');
        } else {
            $this->warn("⚠️  {$devices->count()} appareil(s) marqué(s) hors ligne.");
        }

        return self::SUCCESS;
    }
}
