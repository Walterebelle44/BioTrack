<?php

namespace App\Console\Commands;

use App\Services\MqttService;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttListen extends Command
{
    protected $signature = 'mqtt:listen {--timeout=0 : Timeout en secondes (0 = infini)}';
    protected $description = 'Écoute les messages MQTT des appareils IoT MediTrack';

    public function __construct(private MqttService $mqttService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $host = config('mqtt.host', env('MQTT_HOST', 'localhost'));
        $port = (int) config('mqtt.port', env('MQTT_PORT', 1883));
        $clientId = config('mqtt.client_id', env('MQTT_CLIENT_ID', 'meditrack-server-' . uniqid()));

        $this->info("🔌 Connexion au broker MQTT: {$host}:{$port}");

        try {
            // Username/password doivent être null (et non '') si non définis,
            // sinon la librairie php-mqtt/client rejette la chaîne vide.
            $mqttUsername = env('MQTT_USERNAME');
            $mqttPassword = env('MQTT_PASSWORD');

            $settings = (new ConnectionSettings())
                ->setUsername($mqttUsername !== null && trim($mqttUsername) !== '' ? $mqttUsername : null)
                ->setPassword($mqttPassword !== null && trim($mqttPassword) !== '' ? $mqttPassword : null)
                ->setKeepAliveInterval(60)
                ->setConnectTimeout(10)
                ->setReconnectAutomatically(true);

            // TLS pour AWS IoT Core
            if (env('MQTT_USE_TLS', false)) {
                $settings = $settings
                    ->setUseTls(true)
                    ->setTlsCertificateAuthorityFile(env('MQTT_CA_FILE'))
                    ->setTlsClientCertificateFile(env('MQTT_CERT_FILE'))
                    ->setTlsClientCertificateKeyFile(env('MQTT_KEY_FILE'));
                $port = (int) env('MQTT_PORT', 8883);
            }

            $client = new MqttClient($host, $port, $clientId);
            $client->connect($settings);

            $this->info("✅ Connecté au broker MQTT");

            // S'abonner à tous les topics des appareils
            $client->subscribe('meditrack/devices/+/telemetry', function (string $topic, string $message) {
                $this->line("📡 Message reçu sur: {$topic}");
                $this->mqttService->processPayload($topic, $message);
            }, 1);

            // Topic Last Will Testament (appareil déconnecté)
            $client->subscribe('meditrack/devices/+/status', function (string $topic, string $message) {
                if ($message === 'offline') {
                    $parts = explode('/', $topic);
                    $serialNumber = $parts[2] ?? null;
                    if ($serialNumber) {
                        $this->warn("⚠️  Appareil hors ligne: {$serialNumber}");
                        $this->mqttService->handleDeviceOffline($serialNumber);
                    }
                }
            }, 1);

            $this->info("📥 En écoute sur meditrack/devices/+/telemetry");
            $this->info("   Appuyez sur Ctrl+C pour arrêter");

            $timeout = (int) $this->option('timeout');
            $client->loop(true, $timeout > 0, $timeout);

        } catch (\Exception $e) {
            $this->error("❌ Erreur MQTT: " . $e->getMessage());
            Log::error("MQTT Listen error: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}