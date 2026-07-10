<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id', 'severity', 'type', 'title', 'message',
        'metric_snapshot', 'threshold_value', 'actual_value',
        'status', 'acknowledged_at', 'acknowledged_by',
        'resolved_at', 'resolved_by', 'resolution_note',
        'notification_sent', 'notification_sent_at',
    ];

    protected $casts = [
        'metric_snapshot' => 'array',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'notification_sent' => 'boolean',
        'threshold_value' => 'float',
        'actual_value' => 'float',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function acknowledge(User $user): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);
    }

    public function resolve(User $user, string $note = ''): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'resolution_note' => $note,
        ]);
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'red',
            'warning' => 'orange',
            'info' => 'blue',
            default => 'gray',
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'Critique',
            'warning' => 'Avertissement',
            'info' => 'Information',
            default => $this->severity,
        };
    }
}
