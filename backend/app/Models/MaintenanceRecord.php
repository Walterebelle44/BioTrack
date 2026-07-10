<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    protected $fillable = [
        'device_id', 'performed_by', 'type', 'status',
        'scheduled_date', 'completed_date', 'description',
        'actions_taken', 'cost', 'duration_minutes', 'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'preventif' => 'Maintenance préventive',
            'correctif' => 'Maintenance corrective',
            'calibration' => 'Calibration',
            'remplacement' => 'Remplacement',
            'inspection' => 'Inspection',
            default => $this->type,
        };
    }
}
