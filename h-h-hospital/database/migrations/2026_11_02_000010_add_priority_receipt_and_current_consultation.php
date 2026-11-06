<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->boolean('is_priority')->default(false);
        });
        Schema::table('doctor_days', function (Blueprint $table): void {
            $table->foreignId('current_appointment_id')->nullable()->unique()->constrained('appointments')->restrictOnDelete();
            $table->unsignedInteger('queue_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('doctor_days', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_appointment_id');
            $table->dropColumn('queue_version');
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('is_priority');
        });
    }
};
