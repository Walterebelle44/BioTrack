<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_number', 'name', 'device_profile_id', 'location', 'building', 'floor',
        'manufacturer', 'model', 'installation_date', 'last_maintenance_date',
        'next_maintenance_date', 'status', 'power_state', 'last_metrics',
        'last_seen_at', 'mqtt_certificate', 'mqtt_topic', 'is_active', 'notes',
    ];

    protected $casts = [
        'last_metrics' => 'array',
        'last_seen_at' => 'datetime',
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DeviceProfile::class, 'device_profile_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(DeviceMetric::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function openAlerts(): HasMany
    {
        return $this->hasMany(Alert::class)->where('status', 'open');
    }

    public function latestMetrics(): HasMany
    {
        return $this->hasMany(DeviceMetric::class)->latest('recorded_at')->limit(1);
    }

    public function getMqttTopicAttribute($value): string
    {
        return $value ?? "meditrack/devices/{$this->serial_number}/telemetry";
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'online' => 'green',
            'offline' => 'gray',
            'maintenance' => 'orange',
            'alert' => 'red',
            default => 'gray',
        };
    }

    public function updateFromPayload(array $payload): void
    {
        $metrics = $payload['payload'] ?? [];

        $this->update([
            'status' => 'online',
            'power_state' => $metrics['status'] === 'ON' ? 'on' : ($metrics['status'] === 'STANDBY' ? 'standby' : 'off'),
            'last_metrics' => $metrics,
            'last_seen_at' => now(),
        ]);
    }
}
