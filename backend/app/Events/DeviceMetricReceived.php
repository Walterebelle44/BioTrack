<?php

namespace App\Events;

use App\Models\Device;
use App\Models\DeviceMetric;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceMetricReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Device $device,
        public DeviceMetric $metric,
        public array $rawPayload
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('devices'),
            new Channel("device.{$this->device->serial_number}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->device->id,
            'serial_number' => $this->device->serial_number,
            'name' => $this->device->name,
            'location' => $this->device->location,
            'status' => $this->device->status,
            'power_state' => $this->device->power_state,
            'metrics' => [
                'temperature' => $this->metric->temperature,
                'battery_level' => $this->metric->battery_level,
                'humidity' => $this->metric->humidity,
                'accuracy_index' => $this->metric->accuracy_index,
                'voltage' => $this->metric->voltage,
                'power_state' => $this->metric->power_state,
            ],
            'recorded_at' => $this->metric->recorded_at?->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.metric';
    }
}
