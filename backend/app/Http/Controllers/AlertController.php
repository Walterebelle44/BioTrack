<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Alert::with('device')
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->when($request->severity,  fn($q) => $q->where('severity', $request->severity))
            ->when($request->device_id, fn($q) => $q->where('device_id', $request->device_id))
            ->latest();

        $alerts = $query->paginate($request->per_page ?? 100);

        $stats = [
            'open_critical'  => Alert::where('status', 'open')->where('severity', 'critical')->count(),
            'open_warning'   => Alert::where('status', 'open')->where('severity', 'warning')->count(),
            'open_total'     => Alert::where('status', 'open')->count(),
            'resolved_today' => Alert::where('status', 'resolved')
                ->whereDate('resolved_at', today())->count(),
        ];

        return response()->json([
            // Format compatible frontend (alerts + stats à la racine)
            'alerts' => collect($alerts->items())->map(fn($a) => $this->formatAlert($a)),
            'data'   => collect($alerts->items())->map(fn($a) => $this->formatAlert($a)),
            'meta'   => [
                'current_page' => $alerts->currentPage(),
                'last_page'    => $alerts->lastPage(),
                'total'        => $alerts->total(),
                'per_page'     => $alerts->perPage(),
            ],
            'stats' => $stats,
        ]);
    }

    public function show(Alert $alert): JsonResponse
    {
        $alert->load(['device.profile', 'acknowledgedByUser', 'resolvedByUser']);
        return response()->json($this->formatAlert($alert, true));
    }

    public function acknowledge(Request $request, Alert $alert): JsonResponse
    {
        if ($alert->status !== 'open') {
            return response()->json(['message' => "L'alerte n'est pas ouverte."], 422);
        }

        $alert->acknowledge($request->user());

        return response()->json([
            'message' => 'Alerte prise en charge.',
            'alert'   => $this->formatAlert($alert->fresh()),
        ]);
    }

    public function resolve(Request $request, Alert $alert): JsonResponse
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        if ($alert->status === 'resolved') {
            return response()->json(['message' => "L'alerte est déjà résolue."], 422);
        }

        $alert->resolve($request->user(), $request->resolution_note ?? '');

        return response()->json([
            'message' => 'Alerte résolue.',
            'alert'   => $this->formatAlert($alert->fresh()),
        ]);
    }

    /**
     * PUT /alerts/{id} — appelé par le frontend avec { status: 'acknowledged'|'resolved' }
     */
    public function updateStatus(Request $request, Alert $alert): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:acknowledged,resolved',
        ]);

        if ($request->status === 'acknowledged') {
            if ($alert->status !== 'open') {
                return response()->json(['message' => "L'alerte n'est pas ouverte."], 422);
            }
            $alert->acknowledge($request->user());
        } elseif ($request->status === 'resolved') {
            if ($alert->status === 'resolved') {
                return response()->json(['message' => "L'alerte est déjà résolue."], 422);
            }
            $alert->resolve($request->user(), $request->resolution_note ?? '');
        }

        return response()->json([
            'message' => 'Alerte mise à jour.',
            'alert'   => $this->formatAlert($alert->fresh()),
        ]);
    }

    public function bulkAcknowledge(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ]);

        $count = Alert::whereIn('id', $request->ids)
            ->where('status', 'open')
            ->update([
                'status'          => 'acknowledged',
                'acknowledged_at' => now(),
                'acknowledged_by' => $request->user()->id,
            ]);

        return response()->json(['message' => "{$count} alerte(s) prise(s) en charge."]);
    }

    private function formatAlert(Alert $alert, bool $full = false): array
    {
        $data = [
            'id'              => $alert->id,
            'severity'        => $alert->severity,
            'severity_label'  => $alert->severity_label,
            'type'            => $alert->type,
            'title'           => $alert->title,
            'message'         => $alert->message,
            'status'          => $alert->status,
            'actual_value'    => $alert->actual_value,
            'threshold_value' => $alert->threshold_value,
            'created_at'      => $alert->created_at->toISOString(),
            // Champs plats attendus par le frontend
            'device_name'     => $alert->device?->name,
            'device_location' => $alert->device?->location,
            'device' => $alert->device ? [
                'id'            => $alert->device->id,
                'serial_number' => $alert->device->serial_number,
                'name'          => $alert->device->name,
                'location'      => $alert->device->location,
                'profile'       => $alert->device->profile?->label,
            ] : null,
        ];

        if ($full) {
            $data['metric_snapshot']  = $alert->metric_snapshot;
            $data['acknowledged_at']  = $alert->acknowledged_at?->toISOString();
            $data['acknowledged_by']  = $alert->acknowledgedByUser?->name;
            $data['resolved_at']      = $alert->resolved_at?->toISOString();
            $data['resolved_by']      = $alert->resolvedByUser?->name;
            $data['resolution_note']  = $alert->resolution_note;
        }

        return $data;
    }
}
