<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique(); // ID unique de l'appareil
            $table->string('name'); // Nom lisible: "Frigo Bloc A"
            $table->foreignId('device_profile_id')->constrained()->onDelete('restrict');
            $table->string('location'); // "Bloc opératoire", "Salle 12", "Labo Sang"
            $table->string('building')->nullable(); // Bâtiment
            $table->string('floor')->nullable(); // Étage
            $table->string('manufacturer')->nullable(); // Fabricant
            $table->string('model')->nullable(); // Modèle appareil
            $table->date('installation_date')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();

            // État temps réel (mis à jour par MQTT)
            $table->enum('status', ['online', 'offline', 'maintenance', 'alert', 'unknown'])->default('unknown');
            $table->enum('power_state', ['on', 'off', 'standby', 'unknown'])->default('unknown');
            $table->json('last_metrics')->nullable(); // Dernières valeurs JSON
            $table->timestamp('last_seen_at')->nullable();

            // Certificat MQTT (pour AWS IoT Core)
            $table->text('mqtt_certificate')->nullable();
            $table->string('mqtt_topic')->nullable(); // Topic MQTT dédié

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
