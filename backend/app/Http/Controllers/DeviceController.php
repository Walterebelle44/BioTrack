<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceProfile;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Device::with(['profile', 'openAlerts'])
            ->when($request->status,     fn($q) => $q->where('status', $request->status))
            ->when($request->profile_id, fn($q) => $q->where('device_profile_id', $request->profile_id))
            ->when($request->location,   fn($q) => $q->where('location', 'like', "%{$request->location}%"))
            ->when($request->search,     fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('serial_number', 'like', "%{$request->search}%");
            }))
            ->where('is_active', true);

        $devices = $query->get()->map(fn($d) => $this->formatDevice($d));

        return response()->json($devices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'serial_number'     => 'required|string|unique:devices',
            'name'              => 'required|string|max:255',
            'device_profile_id' => 'required|exists:device_profiles,id',
            'location'          => 'required|string|max:255',
            'building'          => 'nullable|string|max:100',
            'floor'             => 'nullable|string|max:50',
            'manufacturer'      => 'nullable|string|max:100',
            'model'             => 'nullable|string|max:100',
            'installation_date' => 'nullable|date',
            'notes'             => 'nullable|string',
        ]);

        $validated['mqtt_topic'] = "meditrack/devices/{$validated['serial_number']}/telemetry";

        $device = Device::create($validated);
        $device->load('profile');

        return response()->json($this->formatDevice($device), 201);
    }

    public function show(Device $device): JsonResponse
    {
        $device->load([
            'profile',
            'openAlerts',
            'maintenanceRecords' => fn($q) => $q->latest()->limit(5),
        ]);

        // Mapper les statuts maintenance pour le frontend
        $statutMap = ['planifie' => 'planifiee', 'en_cours' => 'en_cours', 'termine' => 'terminee', 'annule' => 'annulee'];

        return response()->json([
            ...$this->formatDevice($device),
            'open_alerts' => $device->openAlerts->map(fn($a) => [
                'id'         => $a->id,
                'severity'   => $a->severity,
                'type'       => $a->type,
                'title'      => $a->title,
                'status'     => $a->status,
                'created_at' => $a->created_at->toISOString(),
            ]),
            'recent_maintenance' => $device->maintenanceRecords->map(fn($m) => [
                'id'             => $m->id,
                'type'           => $m->type,
                'type_label'     => $m->type_label,
                'status'         => $statutMap[$m->status] ?? $m->status,
                'scheduled_date' => $m->scheduled_date?->toDateString(),
                'completed_date' => $m->completed_date?->toDateString(),
                'cost'           => (float) ($m->cost ?? 0),
            ]),
            'mqtt_topic'          => $device->mqtt_topic,
            'installation_date'   => $device->installation_date?->toDateString(),
            'next_maintenance_date'=> $device->next_maintenance_date?->toDateString(),
        ]);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $validated = $request->validate([
            'name'                  => 'sometimes|string|max:255',
            'device_profile_id'     => 'sometimes|exists:device_profiles,id',
            'location'              => 'sometimes|string|max:255',
            'building'              => 'nullable|string|max:100',
            'floor'                 => 'nullable|string|max:50',
            'manufacturer'          => 'nullable|string|max:100',
            'model'                 => 'nullable|string|max:100',
            'installation_date'     => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'notes'                 => 'nullable|string',
            'is_active'             => 'sometimes|boolean',
            // Champs alias envoyés par le DeviceModal
            'nom'                   => 'sometimes|string|max:255',
            'marque'                => 'nullable|string|max:100',
            'modele'                => 'nullable|string|max:100',
            'localisation_detail'   => 'nullable|string|max:255',
            'fournisseur'           => 'nullable|string|max:150',
            'description'           => 'nullable|string',
            'statut'                => 'nullable|string',
        ]);

        // Mapper les noms de champs du frontend (BioTrack) vers le modèle (MediTrack)
        $mapped = [];
        if (isset($validated['nom']))                $mapped['name']         = $validated['nom'];
        if (isset($validated['name']))               $mapped['name']         = $validated['name'];
        if (isset($validated['marque']))             $mapped['manufacturer'] = $validated['marque'];
        if (isset($validated['manufacturer']))       $mapped['manufacturer'] = $validated['manufacturer'];
        if (isset($validated['modele']))             $mapped['model']        = $validated['modele'];
        if (isset($validated['model']))              $mapped['model']        = $validated['model'];
        if (isset($validated['localisation_detail'])) $mapped['location']   = $validated['localisation_detail'];
        if (isset($validated['location']))           $mapped['location']     = $validated['location'];
        if (isset($validated['device_profile_id'])) $mapped['device_profile_id'] = $validated['device_profile_id'];
        if (isset($validated['building']))           $mapped['building']     = $validated['building'];
        if (isset($validated['floor']))              $mapped['floor']        = $validated['floor'];
        if (isset($validated['installation_date'])) $mapped['installation_date'] = $validated['installation_date'];
        if (isset($validated['next_maintenance_date'])) $mapped['next_maintenance_date'] = $validated['next_maintenance_date'];
        if (isset($validated['notes']))              $mapped['notes']        = $validated['notes'];
        if (isset($validated['description']))        $mapped['notes']        = $validated['description'];
        if (isset($validated['is_active']))          $mapped['is_active']    = $validated['is_active'];

        $device->update($mapped);
        $device->load('profile');

        return response()->json($this->formatDevice($device));
    }

    public function destroy(Device $device): JsonResponse
    {
        $device->update(['is_active' => false]);
        return response()->json(['message' => 'Appareil désactivé.']);
    }

    public function metrics(Request $request, Device $device): JsonResponse
    {
        $hours = min((int) ($request->hours ?? 24), 168);
        $metrics = $device->metrics()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'temperature', 'battery_level', 'humidity', 'accuracy_index', 'voltage', 'power_state']);

        return response()->json([
            'device_id'    => $device->id,
            'serial_number'=> $device->serial_number,
            'period_hours' => $hours,
            'count'        => $metrics->count(),
            'metrics'      => $metrics,
        ]);
    }

    public function simulateMqtt(Request $request, Device $device): JsonResponse
    {
        $request->validate([
            'temperature'    => 'nullable|numeric',
            'battery'        => 'nullable|numeric|min:0|max:100',
            'humidity'       => 'nullable|numeric',
            'accuracy_index' => 'nullable|numeric|min:0|max:1',
            'status'         => 'nullable|in:ON,OFF,STANDBY',
        ]);

        $payload = [
            'device_id' => $device->serial_number,
            'timestamp' => time(),
            'payload'   => [
                'status'         => $request->status ?? 'ON',
                'battery'        => $request->battery ?? rand(20, 100),
                'temperature'    => $request->temperature ?? round(rand(20, 42) + rand(0, 9) / 10, 1),
                'accuracy_index' => $request->accuracy_index ?? round(0.95 + rand(0, 4) / 100, 2),
                'humidity'       => $request->humidity ?? rand(40, 65),
            ],
            'metadata' => [
                'profile'  => $device->profile?->name,
                'location' => str_replace(' ', '_', $device->location),
            ],
        ];

        app(MqttService::class)->processPayload($device->mqtt_topic, json_encode($payload));

        return response()->json([
            'message' => 'Simulation MQTT envoyée.',
            'payload' => $payload,
        ]);
    }

    private function formatDevice(Device $device): array
    {
        return [
            'id'                   => $device->id,
            'serial_number'        => $device->serial_number,
            'name'                 => $device->name,
            'location'             => $device->location,
            'building'             => $device->building,
            'floor'                => $device->floor,
            'status'               => $device->status,
            'power_state'          => $device->power_state,
            'last_metrics'         => $device->last_metrics,
            'last_seen_at'         => $device->last_seen_at?->toISOString(),
            'is_active'            => $device->is_active,
            'manufacturer'         => $device->manufacturer,
            'model'                => $device->model,
            'installation_date'    => $device->installation_date?->toDateString(),
            'next_maintenance_date'=> $device->next_maintenance_date?->toDateString(),
            'open_alerts_count'    => $device->openAlerts?->count() ?? 0,
            'mqtt_topic'           => $device->mqtt_topic,
            'notes'                => $device->notes,
            'profile' => $device->profile ? [
                'id'               => $device->profile->id,
                'name'             => $device->profile->name,
                'label'            => $device->profile->label,
                'icon'             => $device->profile->icon,
                'metrics'          => $device->profile->metrics,
                'alert_thresholds' => $device->profile->alert_thresholds,
            ] : null,
        ];
    }
}
