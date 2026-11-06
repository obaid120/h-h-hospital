<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->string('mr_number', 40)->unique();
            $table->string('name', 100)->index();
            $table->string('phone', 16)->index();
            $table->unsignedTinyInteger('age_years');
            $table->uuid('registration_key')->unique();
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('registered_at');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
