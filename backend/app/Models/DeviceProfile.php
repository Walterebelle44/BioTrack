<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'label', 'icon', 'alert_thresholds', 'metrics', 'description',
    ];

    protected $casts = [
        'alert_thresholds' => 'array',
        'metrics' => 'array',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Vérifie si une valeur dépasse les seuils pour une métrique donnée
     */
    public function checkThreshold(string $metric, float $value): ?array
    {
        $thresholds = $this->alert_thresholds ?? [];

        if (!isset($thresholds[$metric])) {
            return null;
        }

        $t = $thresholds[$metric];

        if (isset($t['max']) && $value > $t['max']) {
            return [
                'violated' => true,
                'direction' => 'high',
                'threshold' => $t['max'],
                'actual' => $value,
                'unit' => $t['unit'] ?? '',
            ];
        }

        if (isset($t['min']) && $value < $t['min']) {
            return [
                'violated' => true,
                'direction' => 'low',
                'threshold' => $t['min'],
                'actual' => $value,
                'unit' => $t['unit'] ?? '',
            ];
        }

        return ['violated' => false];
    }
}
