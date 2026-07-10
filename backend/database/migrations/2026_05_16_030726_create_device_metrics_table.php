<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historique des mesures IoT (TimeSeries)
        Schema::create('device_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('serial_number')->index(); // Dénormalisé pour performances
            $table->timestamp('recorded_at')->index(); // Timestamp de la mesure

            // Métriques communes
            $table->float('temperature')->nullable();
            $table->float('battery_level')->nullable(); // %
            $table->float('humidity')->nullable(); // %
            $table->float('accuracy_index')->nullable(); // 0.00 - 1.00
            $table->float('voltage')->nullable(); // V
            $table->enum('power_state', ['on', 'off', 'standby'])->nullable();

            // Payload complet brut (pour métriques custom)
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            // Index composite pour requêtes temporelles
            $table->index(['device_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_metrics');
    }
};
