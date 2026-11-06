<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_cancellations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->restrictOnDelete();
            $table->uuid('request_key')->unique();
            $table->char('payload_hash', 64);
            $table->string('previous_status', 12);
            $table->text('reason');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_name', 100);
            $table->string('actor_role', 20);
            $table->timestamp('cancelled_at')->index();
            $table->string('timezone', 64);
            $table->unsignedTinyInteger('released_capacity')->default(1);
            $table->timestamps();
        });
        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('refund_reference', 40)->unique();
            $table->uuid('request_key')->unique();
            $table->char('payload_hash', 64);
            $table->unsignedInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('method', 10)->default('cash');
            $table->text('reason');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_name', 100);
            $table->string('actor_role', 20);
            $table->timestamp('refunded_at')->index();
            $table->string('timezone', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('appointment_cancellations');
    }
};
