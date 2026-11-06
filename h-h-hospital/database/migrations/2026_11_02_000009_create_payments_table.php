<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('receipt_number', 40)->unique();
            $table->uuid('request_key')->unique();
            $table->char('request_payload_hash', 64);
            $table->unsignedInteger('base_fee_minor');
            $table->unsignedInteger('priority_charge_minor')->default(0);
            $table->unsignedInteger('total_minor');
            $table->string('currency', 3);
            $table->string('method', 10)->default('cash');
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('cashier_name', 100);
            $table->timestamp('paid_at')->index();
            $table->string('timezone', 64);
            $table->string('patient_name', 100);
            $table->string('patient_mr', 40);
            $table->string('patient_phone', 16);
            $table->unsignedTinyInteger('patient_age');
            $table->string('doctor_name', 100);
            $table->date('opd_date');
            $table->unsignedInteger('token_number');
            $table->timestamps();
            $table->index(['cashier_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
