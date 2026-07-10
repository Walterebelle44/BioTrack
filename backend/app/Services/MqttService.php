<?php

namespace App\Services;

use App\Events\DeviceMetricReceived;
use App\Events\AlertTriggered;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceMetric;
use Illuminate\Support\Facades\Log;

class MqttService
{
    /**
     * Traite un payload MQTT reçu d'un appareil
     * Format attendu:
     * {
     *   "device_id": "DS-HGL-001",
     *   "timestamp": 1706274456,
     *   "payload": {
     *     "status": "ON",
     *     "battery": 85,
     *     "temperature": 4.2,
     *     "accuracy_index": 0.98,
     *     "humidity": 45.2
     *   },
     *   "metadata": {
     *     "profile": "refrigerator",
     *     "location": "Labo_Sang"
     *   }
     * }
     */
    public function processPayload(string $topic, string $rawPayload): void
    {
        try {
            $data = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error("MqttService: Payload JSON invalide sur {$topic}: " . $e->getMessage());
            return;
        }

        $serialNumber = $data['device_id'] ?? $this->extractSerialFromTopic($topic);

        if (!$serialNumber) {
            Log::warning("MqttService: Impossible de déterminer l'appareil pour {$topic}");
            return;
        }

        $device = Device::where('serial_number', $serialNumber)->first();

        if (!$device) {
            Log::warning("MqttService: Appareil inconnu: {$serialNumber}");
            return;
        }

        // 1. Mettre à jour l'état de l'appareil
        $device->updateFromPayload($data);

        // 2. Enregistrer la métrique historique
        $metric = DeviceMetric::fromMqttPayload($device, $data);

        // 3. Vérifier les seuils et créer les alertes
        $this->checkThresholds($device, $data['payload'] ?? []);

        // 4. Broadcaster via WebSocket
        broadcast(new DeviceMetricReceived($device, $metric, $data));

        Log::info("MqttService: Payload traité pour {$serialNumber}");
    }

    /**
     * Marque un appareil comme hors-ligne (Keep-alive MQTT expiré)
     */
    public function handleDeviceOffline(string $serialNumber): void
    {
        $device = Device::where('serial_number', $serialNumber)->first();
        if (!$device) return;

        if ($device->status === 'online') {
            $device->update(['status' => 'offline']);

            $alert = Alert::create([
                'device_id' => $device->id,
                'severity' => 'critical',
                'type' => 'device_offline',
                'title' => "Appareil hors ligne : {$device->name}",
                'message' => "L'appareil {$device->name} ({$device->serial_number}) situé en {$device->location} ne répond plus. Vérifiez la connexion réseau et l'alimentation.",
                'metric_snapshot' => $device->last_metrics,
            ]);

            broadcast(new AlertTriggered($alert));
        }
    }

    /**
     * Vérifie les seuils d'alerte pour toutes les métriques reçues
     */
    private function checkThresholds(Device $device, array $metrics): void
    {
        $profile = $device->profile;
        if (!$profile) return;

        $metricMap = [
            'temperature' => 'temperature',
            'battery' => 'battery_level',
            'battery_level' => 'battery_level',
            'humidity' => 'humidity',
            'accuracy_index' => 'accuracy_index',
        ];

        foreach ($metrics as $key => $value) {
            if (!is_numeric($value)) continue;

            $result = $profile->checkThreshold($key, (float) $value);

            if ($result && $result['violated']) {
                $this->createThresholdAlert($device, $key, $result, $metrics);
            }
        }
    }

    private function createThresholdAlert(Device $device, string $metric, array $result, array $metrics): void
    {
        // Éviter les doublons : pas d'alerte ouverte du même type
        $existing = Alert::where('device_id', $device->id)
            ->where('type', $this->getAlertType($metric, $result['direction']))
            ->where('status', 'open')
            ->exists();

        if ($existing) return;

        $alertType = $this->getAlertType($metric, $result['direction']);
        $severity = $this->getSeverity($metric, $result);

        $alert = Alert::create([
            'device_id' => $device->id,
            'severity' => $severity,
            'type' => $alertType,
            'title' => $this->getAlertTitle($device, $metric, $result),
            'message' => $this->getAlertMessage($device, $metric, $result),
            'metric_snapshot' => $metrics,
            'threshold_value' => $result['threshold'],
            'actual_value' => $result['actual'],
        ]);

        broadcast(new AlertTriggered($alert));
    }

    private function getAlertType(string $metric, string $direction): string
    {
        return match(true) {
            $metric === 'temperature' && $direction === 'high' => 'temperature_high',
            $metric === 'temperature' && $direction === 'low' => 'temperature_low',
            in_array($metric, ['battery', 'battery_level']) && $direction === 'low' => 'battery_low',
            $metric === 'humidity' && $direction === 'high' => 'humidity_high',
            $metric === 'humidity' && $direction === 'low' => 'humidity_low',
            $metric === 'accuracy_index' && $direction === 'low' => 'accuracy_low',
            default => 'custom',
        };
    }

    private function getSeverity(string $metric, array $result): string
    {
        if (in_array($metric, ['battery', 'battery_level']) && $result['actual'] < 10) {
            return 'critical';
        }
        if ($metric === 'accuracy_index' && $result['actual'] < 0.85) {
            return 'critical';
        }
        return 'warning';
    }

    private function getAlertTitle(Device $device, string $metric, array $result): string
    {
        $labels = [
            'temperature' => 'Température',
            'battery' => 'Batterie',
            'battery_level' => 'Batterie',
            'humidity' => 'Humidité',
            'accuracy_index' => 'Précision',
        ];

        $label = $labels[$metric] ?? $metric;
        $direction = $result['direction'] === 'high' ? 'élevée' : 'basse';
        return "{$label} anormalement {$direction} - {$device->name}";
    }

    private function getAlertMessage(Device $device, string $metric, array $result): string
    {
        $unit = $result['unit'] ?? '';
        $direction = $result['direction'] === 'high' ? 'dépasse le maximum' : 'est en dessous du minimum';
        return sprintf(
            "L'appareil %s (%s) en %s : %s %.2f%s (seuil: %.2f%s). Intervention requise.",
            $device->name,
            $device->serial_number,
            $device->location,
            $direction,
            $result['actual'],
            $unit,
            $result['threshold'],
            $unit
        );
    }

    private function extractSerialFromTopic(string $topic): ?string
    {
        // Format: meditrack/devices/{serial}/telemetry
        $parts = explode('/', $topic);
        return $parts[2] ?? null;
    }
}
