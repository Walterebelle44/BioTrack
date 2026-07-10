<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Alert $alert) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('alerts'),
            new Channel("device.{$this->alert->device->serial_number}"),
        ];
    }

    public function broadcastWith(): array
    {
        $device = $this->alert->device;
        return [
            'id' => $this->alert->id,
            'severity' => $this->alert->severity,
            'type' => $this->alert->type,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'status' => $this->alert->status,
            'actual_value' => $this->alert->actual_value,
            'threshold_value' => $this->alert->threshold_value,
            'created_at' => $this->alert->created_at->toISOString(),
            'device' => [
                'id' => $device->id,
                'serial_number' => $device->serial_number,
                'name' => $device->name,
                'location' => $device->location,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'alert.triggered';
    }
}
