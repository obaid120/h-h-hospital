<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $table->foreignId('authored_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('version')->default(0);
            $table->uuid('last_save_key')->unique();
            $table->char('last_save_hash', 64);
            $table->text('notes')->nullable();
            $table->text('advice')->nullable();
            $table->string('patient_name', 100);
            $table->string('patient_mr', 40);
            $table->unsignedTinyInteger('patient_age');
            $table->string('doctor_name', 100);
            $table->string('doctor_specialization', 150);
            $table->string('hospital_name', 200);
            $table->text('hospital_address')->nullable();
            $table->string('hospital_phone', 100)->nullable();
            $table->string('timezone', 64);
            $table->date('opd_date');
            $table->unsignedInteger('token_number');
            $table->timestamp('draft_saved_at');
            $table->timestamp('finalized_at')->nullable()->index();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->uuid('completion_key')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('prescription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('medicine_name', 150);
            $table->string('dose', 100)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->string('duration', 100)->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();
            $table->unique(['prescription_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
