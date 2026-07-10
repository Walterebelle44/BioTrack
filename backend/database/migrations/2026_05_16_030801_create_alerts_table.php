<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->enum('type', [
                'temperature_high', 'temperature_low',
                'battery_low', 'battery_critical',
                'humidity_high', 'humidity_low',
                'accuracy_low',
                'device_offline',
                'device_online',
                'maintenance_due',
                'custom'
            ]);
            $table->string('title');
            $table->text('message');
            $table->json('metric_snapshot')->nullable(); // Valeurs au moment de l'alerte
            $table->float('threshold_value')->nullable(); // Seuil configuré
            $table->float('actual_value')->nullable(); // Valeur mesurée

            // Gestion du cycle de vie
            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->text('resolution_note')->nullable();

            // Notification
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();

            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['severity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
