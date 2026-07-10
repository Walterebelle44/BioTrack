<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Utilisateurs ──────────────────────────────────────────────────────
        User::create([
            'name'     => 'Admin BioTrack',
            'email'    => 'admin@biotrack.cm',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'service'  => 'Direction',
            'phone'    => '+237600000001',
        ]);

        User::create([
            'name'     => 'Dr. Nguema Paul',
            'email'    => 'directeur@biotrack.cm',
            'password' => Hash::make('password'),
            'role'     => 'directeur',
            'service'  => 'Direction Générale',
            'phone'    => '+237600000002',
        ]);

        $technicien = User::create([
            'name'     => 'Mvondo Jean',
            'email'    => 'tech@biotrack.cm',
            'password' => Hash::make('password'),
            'role'     => 'technicien',
            'service'  => 'Maintenance Biomédicale',
            'phone'    => '+237600000003',
        ]);

        User::create([
            'name'     => 'Bello Fatima',
            'email'    => 'si@biotrack.cm',
            'password' => Hash::make('password'),
            'role'     => 'responsable_si',
            'service'  => 'Informatique',
            'phone'    => '+237600000004',
        ]);

        // ── Profils d'appareils ───────────────────────────────────────────────
        $refrigerateur = DeviceProfile::create([
            'name'        => 'refrigerator',
            'label'       => 'Réfrigérateur médical',
            'icon'        => 'thermometer',
            'description' => 'Conservation des vaccins, médicaments et échantillons biologiques',
            'metrics'     => ['temperature', 'humidity', 'battery_level', 'accuracy_index'],
            'alert_thresholds' => [
                'temperature'    => ['min' => 2,    'max' => 8,   'unit' => '°C'],
                'humidity'       => ['min' => 30,   'max' => 70,  'unit' => '%'],
                'battery_level'  => ['min' => 20,               'unit' => '%'],
                'accuracy_index' => ['min' => 0.95],
            ],
        ]);

        $pousseSeringue = DeviceProfile::create([
            'name'        => 'syringe_pump',
            'label'       => 'Pousse-seringue',
            'icon'        => 'activity',
            'description' => 'Administration précise de médicaments par voie intraveineuse',
            'metrics'     => ['battery_level', 'accuracy_index'],
            'alert_thresholds' => [
                'battery_level'  => ['min' => 15, 'unit' => '%'],
                'accuracy_index' => ['min' => 0.98],
            ],
        ]);

        $concentrateur = DeviceProfile::create([
            'name'        => 'oxygen_concentrator',
            'label'       => "Concentrateur d'oxygène",
            'icon'        => 'wind',
            'description' => "Production d'oxygène médical pour patients",
            'metrics'     => ['temperature', 'battery_level', 'humidity'],
            'alert_thresholds' => [
                'temperature'   => ['min' => 5,  'max' => 35, 'unit' => '°C'],
                'battery_level' => ['min' => 25, 'unit' => '%'],
                'humidity'      => ['max' => 80, 'unit' => '%'],
            ],
        ]);

        $moniteur = DeviceProfile::create([
            'name'        => 'patient_monitor',
            'label'       => 'Moniteur de patient',
            'icon'        => 'heart',
            'description' => 'Surveillance constante des paramètres vitaux',
            'metrics'     => ['battery_level', 'accuracy_index'],
            'alert_thresholds' => [
                'battery_level'  => ['min' => 20, 'unit' => '%'],
                'accuracy_index' => ['min' => 0.97],
            ],
        ]);

        $defibrillateur = DeviceProfile::create([
            'name'        => 'defibrillator',
            'label'       => 'Défibrillateur',
            'icon'        => 'zap',
            'description' => 'Défibrillateur semi-automatique pour urgences cardiaques',
            'metrics'     => ['battery_level', 'accuracy_index'],
            'alert_thresholds' => [
                'battery_level'  => ['min' => 30, 'unit' => '%'],
                'accuracy_index' => ['min' => 0.99],
            ],
        ]);

        // ── Appareils ─────────────────────────────────────────────────────────
        $appareils = [
            [
                'serial_number'     => 'DS-LB-001',
                'name'              => 'Frigo Labo Sang',
                'device_profile_id' => $refrigerateur->id,
                'location'          => 'Laboratoire Sang',
                'building'          => 'Bâtiment A',
                'floor'             => 'RDC',
                'manufacturer'      => 'DOMETIC',
                'model'             => 'HCL 130',
                'status'            => 'online',
                'power_state'       => 'on',
                'last_metrics'      => ['status' => 'ON', 'temperature' => 4.2, 'battery' => 85, 'humidity' => 52, 'accuracy_index' => 0.98],
                'last_seen_at'      => now()->subMinutes(2),
                'installation_date' => '2023-03-15',
            ],
            [
                'serial_number'     => 'DS-BL-001',
                'name'              => 'Frigo Bloc Op. A',
                'device_profile_id' => $refrigerateur->id,
                'location'          => 'Bloc Opératoire',
                'building'          => 'Bâtiment B',
                'floor'             => '1er étage',
                'manufacturer'      => 'ZANUSSI',
                'model'             => 'ZEAN11FW0',
                'status'            => 'alert',
                'power_state'       => 'on',
                'last_metrics'      => ['status' => 'ON', 'temperature' => 9.5, 'battery' => 72, 'humidity' => 45, 'accuracy_index' => 0.97],
                'last_seen_at'      => now()->subMinutes(5),
                'installation_date' => '2022-06-10',
            ],
            [
                'serial_number'     => 'PS-REA-001',
                'name'              => 'Pousse-seringue Réa 1',
                'device_profile_id' => $pousseSeringue->id,
                'location'          => 'Réanimation',
                'building'          => 'Bâtiment A',
                'floor'             => '2ème étage',
                'manufacturer'      => 'B.BRAUN',
                'model'             => 'Perfusor Compact S',
                'status'            => 'online',
                'power_state'       => 'on',
                'last_metrics'      => ['status' => 'ON', 'battery' => 67, 'accuracy_index' => 0.99],
                'last_seen_at'      => now()->subMinutes(1),
                'installation_date' => '2023-01-08',
            ],
            [
                'serial_number'     => 'PS-REA-002',
                'name'              => 'Pousse-seringue Réa 2',
                'device_profile_id' => $pousseSeringue->id,
                'location'          => 'Réanimation',
                'building'          => 'Bâtiment A',
                'floor'             => '2ème étage',
                'manufacturer'      => 'B.BRAUN',
                'model'             => 'Perfusor Compact S',
                'status'            => 'offline',
                'power_state'       => 'off',
                'last_metrics'      => ['status' => 'OFF', 'battery' => 8, 'accuracy_index' => 0.96],
                'last_seen_at'      => now()->subHours(3),
                'installation_date' => '2022-11-20',
            ],
            [
                'serial_number'     => 'OC-URG-001',
                'name'              => "Concentrateur O2 Urgences",
                'device_profile_id' => $concentrateur->id,
                'location'          => 'Urgences',
                'building'          => 'Bâtiment C',
                'floor'             => 'RDC',
                'manufacturer'      => 'INVACARE',
                'model'             => 'Platinum 10',
                'status'            => 'online',
                'power_state'       => 'on',
                'last_metrics'      => ['status' => 'ON', 'temperature' => 28.5, 'battery' => 91, 'humidity' => 62],
                'last_seen_at'      => now()->subMinutes(3),
                'installation_date' => '2023-07-01',
            ],
            [
                'serial_number'     => 'PM-CHIR-001',
                'name'              => 'Moniteur Chirurgie 1',
                'device_profile_id' => $moniteur->id,
                'location'          => 'Salle de Chirurgie',
                'building'          => 'Bâtiment B',
                'floor'             => '1er étage',
                'manufacturer'      => 'MINDRAY',
                'model'             => 'iMEC 8',
                'status'            => 'maintenance',
                'power_state'       => 'off',
                'last_metrics'      => ['status' => 'OFF', 'battery' => 45, 'accuracy_index' => 0.93],
                'last_seen_at'      => now()->subDays(1),
                'installation_date' => '2022-09-15',
            ],
            [
                'serial_number'     => 'DEF-URG-001',
                'name'              => 'Défibrillateur Urgences',
                'device_profile_id' => $defibrillateur->id,
                'location'          => 'Urgences',
                'building'          => 'Bâtiment C',
                'floor'             => 'RDC',
                'manufacturer'      => 'ZOLL',
                'model'             => 'AED Plus',
                'status'            => 'online',
                'power_state'       => 'standby',
                'last_metrics'      => ['status' => 'STANDBY', 'battery' => 95, 'accuracy_index' => 0.99],
                'last_seen_at'      => now()->subMinutes(8),
                'installation_date' => '2023-05-20',
            ],
        ];

        foreach ($appareils as $data) {
            Device::create($data);
        }

        // ── Alertes ───────────────────────────────────────────────────────────
        \App\Models\Alert::create([
            'device_id'       => 2,
            'severity'        => 'critical',
            'type'            => 'temperature_high',
            'title'           => 'Température trop élevée - Frigo Bloc Op. A',
            'message'         => "L'appareil Frigo Bloc Op. A (DS-BL-001) situé en Bloc Opératoire : dépasse le maximum 9.50°C (seuil: 8.00°C). Intervention requise.",
            'metric_snapshot' => ['temperature' => 9.5, 'battery' => 72, 'status' => 'ON'],
            'threshold_value' => 8.0,
            'actual_value'    => 9.5,
            'status'          => 'open',
        ]);

        \App\Models\Alert::create([
            'device_id'       => 4,
            'severity'        => 'critical',
            'type'            => 'battery_low',
            'title'           => 'Batterie critique - Pousse-seringue Réa 2',
            'message'         => "L'appareil PS-REA-002 en Réanimation : batterie à 8% (seuil minimum: 15%). Rechargement immédiat requis.",
            'threshold_value' => 15.0,
            'actual_value'    => 8.0,
            'status'          => 'open',
        ]);

        \App\Models\Alert::create([
            'device_id' => 4,
            'severity'  => 'critical',
            'type'      => 'device_offline',
            'title'     => 'Appareil hors ligne : Pousse-seringue Réa 2',
            'message'   => "L'appareil PS-REA-002 en Réanimation ne répond plus.",
            'status'    => 'open',
        ]);

        \App\Models\Alert::create([
            'device_id' => 2,
            'severity'  => 'warning',
            'type'      => 'accuracy_low',
            'title'     => 'Précision dégradée - Frigo Bloc Op. A',
            'message'   => "Précision du capteur de température en dessous du seuil acceptable.",
            'threshold_value' => 0.98,
            'actual_value'    => 0.97,
            'status'    => 'acknowledged',
            'acknowledged_at' => now()->subHours(1),
        ]);

        // ── Maintenances ──────────────────────────────────────────────────────
        \App\Models\MaintenanceRecord::create([
            'device_id'      => 6,
            'performed_by'   => $technicien->id,
            'type'           => 'calibration',
            'status'         => 'en_cours',
            'scheduled_date' => today(),
            'description'    => 'Calibration annuelle du moniteur de patient - vérification précision',
            'cost'           => 35000,
        ]);

        \App\Models\MaintenanceRecord::create([
            'device_id'      => 1,
            'performed_by'   => $technicien->id,
            'type'           => 'preventif',
            'status'         => 'planifie',
            'scheduled_date' => today()->addDays(3),
            'description'    => 'Nettoyage et vérification des joints du réfrigérateur',
            'cost'           => 15000,
        ]);

        \App\Models\MaintenanceRecord::create([
            'device_id'      => 5,
            'performed_by'   => $technicien->id,
            'type'           => 'preventif',
            'status'         => 'planifie',
            'scheduled_date' => today()->addDays(7),
            'description'    => 'Maintenance préventive trimestrielle du concentrateur O2',
            'cost'           => 20000,
        ]);
    }
}
