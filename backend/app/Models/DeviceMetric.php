<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMetric extends Model
{
    protected $fillable = [
        'device_id', 'serial_number', 'recorded_at',
        'temperature', 'battery_level', 'humidity',
        'accuracy_index', 'voltage', 'power_state', 'raw_payload',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'raw_payload' => 'array',
        'temperature' => 'float',
        'battery_level' => 'float',
        'humidity' => 'float',
        'accuracy_index' => 'float',
        'voltage' => 'float',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Crée un enregistrement depuis un payload MQTT
     */
    public static function fromMqttPayload(Device $device, array $payload): self
    {
        $p = $payload['payload'] ?? [];

        return self::create([
            'device_id' => $device->id,
            'serial_number' => $device->serial_number,
            'recorded_at' => isset($payload['timestamp'])
                ? \Carbon\Carbon::createFromTimestamp($payload['timestamp'])
                : now(),
            'temperature' => $p['temperature'] ?? null,
            'battery_level' => $p['battery'] ?? null,
            'humidity' => $p['humidity'] ?? null,
            'accuracy_index' => $p['accuracy_index'] ?? null,
            'voltage' => $p['voltage'] ?? null,
            'power_state' => isset($p['status']) ? strtolower($p['status']) : null,
            'raw_payload' => $payload,
        ]);
    }
}
