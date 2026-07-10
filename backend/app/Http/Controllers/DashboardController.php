<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceMetric;
use App\Models\MaintenanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $devices = Device::where('is_active', true)->get();

        $statusCounts = $devices->groupBy('status')->map->count();

        $alertStats = Alert::selectRaw('severity, status, COUNT(*) as count')
            ->groupBy('severity', 'status')
            ->get()
            ->groupBy('status');

        $recentAlerts = Alert::with('device')
            ->where('status', 'open')
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'severity' => $a->severity,
                'title' => $a->title,
                'device_name' => $a->device?->name,
                'device_location' => $a->device?->location,
                'created_at' => $a->created_at->diffForHumans(),
                'created_at_iso' => $a->created_at->toISOString(),
            ]);

        $upcomingMaintenance = MaintenanceRecord::with('device')
            ->where('status', 'planifie')
            ->where('scheduled_date', '<=', now()->addDays(7))
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'device_name' => $m->device?->name,
                'type_label' => $m->type_label,
                'scheduled_date' => $m->scheduled_date->toDateString(),
                'days_until' => now()->diffInDays($m->scheduled_date, false),
            ]);

        return response()->json([
            'devices' => [
                'total' => $devices->count(),
                'online' => $statusCounts['online'] ?? 0,
                'offline' => $statusCounts['offline'] ?? 0,
                'alert' => $statusCounts['alert'] ?? 0,
                'maintenance' => $statusCounts['maintenance'] ?? 0,
                'availability_rate' => $devices->count() > 0
                    ? round(($statusCounts['online'] ?? 0) / $devices->count() * 100, 1)
                    : 0,
            ],
            'alerts' => [
                'open_critical' => Alert::where('status', 'open')->where('severity', 'critical')->count(),
                'open_warning' => Alert::where('status', 'open')->where('severity', 'warning')->count(),
                'open_total' => Alert::where('status', 'open')->count(),
                'resolved_today' => Alert::where('status', 'resolved')
                    ->whereDate('resolved_at', today())->count(),
            ],
            'recent_alerts' => $recentAlerts,
            'upcoming_maintenance' => $upcomingMaintenance,
            'last_updated' => now()->toISOString(),
        ]);
    }

    public function deviceStatuses(): JsonResponse
    {
        $devices = Device::with('profile')
            ->where('is_active', true)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'serial_number' => $d->serial_number,
                'name' => $d->name,
                'location' => $d->location,
                'status' => $d->status,
                'power_state' => $d->power_state,
                'last_metrics' => $d->last_metrics,
                'last_seen_at' => $d->last_seen_at?->toISOString(),
                'profile_label' => $d->profile?->label,
                'profile_icon' => $d->profile?->icon,
            ]);

        return response()->json($devices);
    }

    public function alertsTimeline(int $days = 7): JsonResponse
    {
        $data = Alert::selectRaw('DATE(created_at) as date, severity, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date', 'severity')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    public function roiCalculator(): JsonResponse
    {
        $totalDevices = Device::where('is_active', true)->count();
        $annualSubscription = $totalDevices * 5000 * 12; // XAF
        $installationCost = 50000; // XAF par défaut

        $preventedFailures = Alert::where('type', 'device_offline')
            ->where('status', 'resolved')
            ->whereYear('created_at', now()->year)
            ->count();

        $maintenanceSavings = MaintenanceRecord::where('type', 'preventif')
            ->whereYear('completed_date', now()->year)
            ->sum('cost');

        return response()->json([
            'total_devices' => $totalDevices,
            'annual_subscription_xaf' => $annualSubscription,
            'installation_cost_xaf' => $installationCost,
            'total_cost_year_1' => $annualSubscription + $installationCost,
            'prevented_failures' => $preventedFailures,
            'estimated_savings_xaf' => $preventedFailures * 250000, // Coût moyen panne évitée
            'maintenance_savings_xaf' => (float) $maintenanceSavings,
            'roi_percent' => $annualSubscription > 0
                ? round(($preventedFailures * 250000 - $annualSubscription) / $annualSubscription * 100, 1)
                : 0,
        ]);
    }
}
