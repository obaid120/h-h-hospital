<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('specialization', 120)->index();
            $table->text('biography')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('normal_fee_minor');
            $table->unsignedInteger('priority_surcharge_minor')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('doctor_weekly_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps();
            $table->unique(['doctor_id', 'weekday']);
        });

        Schema::create('doctor_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('capacity')->default(50);
            $table->unsignedTinyInteger('used_capacity')->default(0);
            $table->unsignedInteger('last_token')->default(0);
            $table->string('schedule_override', 10)->nullable();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['doctor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_days');
        Schema::dropIfExists('doctor_weekly_schedules');
        Schema::dropIfExists('doctors');
    }
};
