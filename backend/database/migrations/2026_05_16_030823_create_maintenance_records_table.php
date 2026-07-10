<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('performed_by')->nullable()->constrained('users');
            $table->enum('type', ['preventif', 'correctif', 'calibration', 'remplacement', 'inspection']);
            $table->enum('status', ['planifie', 'en_cours', 'termine', 'annule'])->default('planifie');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->text('description');
            $table->text('actions_taken')->nullable();
            $table->decimal('cost', 12, 2)->nullable(); // En XAF
            $table->integer('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
