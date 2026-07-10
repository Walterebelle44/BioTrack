<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ex: "refrigerateur", "pousse_seringue", "concentrateur_o2"
            $table->string('label'); // Libellé humain: "Réfrigérateur médical"
            $table->string('icon')->nullable(); // Nom icône
            $table->json('alert_thresholds'); // Seuils d'alerte paramétrables
            // Structure: {
            //   "temperature": {"min": 2, "max": 8, "unit": "°C"},
            //   "battery": {"min": 20, "unit": "%"},
            //   "accuracy_index": {"min": 0.95},
            //   "humidity": {"min": 30, "max": 70, "unit": "%"}
            // }
            $table->json('metrics'); // Métriques surveillées: ["temperature","battery","humidity","accuracy_index"]
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_profiles');
    }
};
