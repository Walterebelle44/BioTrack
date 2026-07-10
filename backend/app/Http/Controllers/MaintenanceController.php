<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRecord;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $records = MaintenanceRecord::with(['device', 'technician'])
            ->when($request->device_id,      fn($q) => $q->where('device_id', $request->device_id))
            ->when($request->equipement_id,  fn($q) => $q->where('device_id', $request->equipement_id))
            ->when($request->status,         fn($q) => $q->where('status', $request->status))
            ->when($request->type,           fn($q) => $q->where('type', $request->type))
            ->orderBy('scheduled_date', 'desc')
            ->get();

        // Retourner un tableau simple (compatible avec le frontend qui attend un array direct)
        return response()->json($records->map(fn($m) => $this->formatRecord($m))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Accepter device_id ou equipement_id (alias)
            'device_id'       => 'required_without:equipement_id|exists:devices,id',
            'equipement_id'   => 'required_without:device_id|exists:devices,id',
            'type'            => 'required|in:preventif,correctif,calibration,remplacement,inspection,preventive,corrective,predictive',
            'scheduled_date'  => 'required|date',
            'description'     => 'nullable|string|max:500',
            'titre'           => 'nullable|string|max:200',   // alias frontend
            'performed_by'    => 'nullable|exists:users,id',
            'technicien_id'   => 'nullable|exists:users,id',  // alias frontend
            'cost'            => 'nullable|numeric|min:0',
            'cout_pieces'     => 'nullable|numeric|min:0',    // alias frontend
            'cout_main_oeuvre'=> 'nullable|numeric|min:0',    // alias frontend
            'notes'           => 'nullable|string',
            'priorite'        => 'nullable|string',
            'statut'          => 'nullable|string',           // alias frontend
        ]);

        // Normaliser device_id
        $deviceId = $validated['device_id'] ?? $validated['equipement_id'];

        // Normaliser type (preventive → preventif, etc.)
        $typeMap = [
            'preventive' => 'preventif',
            'corrective' => 'correctif',
            'predictive' => 'preventif', // pas de type prédictif dans le modèle
        ];
        $type = $typeMap[$validated['type']] ?? $validated['type'];

        // Normaliser statut
        $statutMap = [
            'planifiee' => 'planifie',
            'en_cours'  => 'en_cours',
            'terminee'  => 'termine',
            'annulee'   => 'annule',
        ];
        $status = $statutMap[$validated['statut'] ?? ''] ?? 'planifie';

        // Coût total
        $cost = $validated['cost']
            ?? (($validated['cout_pieces'] ?? 0) + ($validated['cout_main_oeuvre'] ?? 0))
            ?: null;

        // Description : utiliser titre si description vide
        $description = $validated['description']
            ?? $validated['titre']
            ?? 'Intervention planifiée';

        $record = MaintenanceRecord::create([
            'device_id'      => $deviceId,
            'performed_by'   => $validated['performed_by'] ?? $validated['technicien_id'] ?? null,
            'type'           => $type,
            'status'         => $status,
            'scheduled_date' => $validated['scheduled_date'],
            'description'    => $description,
            'cost'           => $cost,
            'notes'          => $validated['notes'] ?? null,
        ]);

        // Mettre à jour la prochaine date de maintenance sur l'appareil
        $device = Device::find($deviceId);
        if ($device) {
            $device->update(['next_maintenance_date' => $validated['scheduled_date']]);
        }

        $record->load(['device', 'technician']);

        return response()->json($this->formatRecord($record), 201);
    }

    public function update(Request $request, MaintenanceRecord $maintenance): JsonResponse
    {
        $validated = $request->validate([
            'status'           => 'sometimes|in:planifie,en_cours,termine,annule,planifiee,terminee,annulee',
            'statut'           => 'sometimes|string', // alias
            'completed_date'   => 'nullable|date',
            'actions_taken'    => 'nullable|string',
            'actions_effectuees' => 'nullable|string', // alias
            'cost'             => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'notes'            => 'nullable|string',
        ]);

        // Normaliser statut
        $statutMap = [
            'planifiee' => 'planifie',
            'terminee'  => 'termine',
            'annulee'   => 'annule',
        ];

        $updateData = [];

        if (isset($validated['status'])) {
            $updateData['status'] = $statutMap[$validated['status']] ?? $validated['status'];
        }
        if (isset($validated['statut'])) {
            $updateData['status'] = $statutMap[$validated['statut']] ?? $validated['statut'];
        }
        if (isset($validated['completed_date']))   $updateData['completed_date']   = $validated['completed_date'];
        if (isset($validated['actions_taken']))     $updateData['actions_taken']     = $validated['actions_taken'];
        if (isset($validated['actions_effectuees'])) $updateData['actions_taken']   = $validated['actions_effectuees'];
        if (isset($validated['cost']))              $updateData['cost']              = $validated['cost'];
        if (isset($validated['duration_minutes']))  $updateData['duration_minutes']  = $validated['duration_minutes'];
        if (isset($validated['notes']))             $updateData['notes']             = $validated['notes'];

        $maintenance->update($updateData);

        // Si terminée → remettre l'appareil en ligne
        $finalStatus = $updateData['status'] ?? $maintenance->status;
        if ($finalStatus === 'termine' && $maintenance->device) {
            $maintenance->device->update([
                'last_maintenance_date' => $updateData['completed_date'] ?? today(),
                'status' => 'online',
            ]);
        }

        return response()->json($this->formatRecord($maintenance->fresh(['device', 'technician'])));
    }

    private function formatRecord(MaintenanceRecord $m): array
    {
        // Mapper les statuts internes vers les valeurs attendues par le frontend
        $statutMap = [
            'planifie'  => 'planifiee',
            'en_cours'  => 'en_cours',
            'termine'   => 'terminee',
            'annule'    => 'annulee',
        ];

        return [
            'id'               => $m->id,
            'type'             => $m->type,
            'type_label'       => $m->type_label,
            'status'           => $statutMap[$m->status] ?? $m->status,
            'title'            => $m->description ?? $m->type_label,
            'scheduled_date'   => $m->scheduled_date?->toDateString(),
            'completed_date'   => $m->completed_date?->toDateString(),
            'description'      => $m->description,
            'actions_taken'    => $m->actions_taken,
            'cost'             => (float) ($m->cost ?? 0),
            'duration_minutes' => $m->duration_minutes,
            'notes'            => $m->notes,
            // Champs plats attendus par le frontend
            'device_name'      => $m->device?->name,
            'device_reference' => $m->device?->serial_number,
            'technicien'       => $m->technician?->name,
            // Objets imbriqués
            'device' => $m->device ? [
                'id'            => $m->device->id,
                'name'          => $m->device->name,
                'serial_number' => $m->device->serial_number,
                'location'      => $m->device->location,
            ] : null,
            'technician' => $m->technician ? [
                'id'   => $m->technician->id,
                'name' => $m->technician->name,
            ] : null,
            'created_at' => $m->created_at->toISOString(),
        ];
    }
}
