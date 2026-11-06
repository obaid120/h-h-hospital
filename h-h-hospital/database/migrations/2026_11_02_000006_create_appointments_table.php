<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('public_reference', 40)->unique();
            $table->uuid('request_key')->unique();
            $table->foreignId('doctor_day_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('submitted_name', 100);
            $table->string('submitted_phone', 16);
            $table->unsignedTinyInteger('submitted_age');
            $table->unsignedTinyInteger('verified_age')->nullable();
            $table->enum('source', ['online', 'walk_in']);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->boolean('is_priority')->default(false);
            $table->unsignedInteger('token_number')->nullable();
            $table->unsignedInteger('normal_fee_minor');
            $table->unsignedInteger('priority_surcharge_minor')->default(0);
            $table->foreignId('receptionist_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('consultation_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->unique(['doctor_day_id', 'token_number']);
            $table->index(['doctor_day_id', 'status', 'is_priority', 'token_number'], 'appointments_queue_index');
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
