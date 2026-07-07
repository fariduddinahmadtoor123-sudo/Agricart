<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_controls', function (Blueprint $table): void {
            $table->id();
            $table->string('control_number', 32)->unique();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quantity_controls', function (Blueprint $table): void {
            $table->id();
            $table->string('control_number', 32)->unique();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('secret_coding_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('profile_number', 32)->unique();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->json('digit_slots')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_coding_profiles');
        Schema::dropIfExists('quantity_controls');
        Schema::dropIfExists('price_controls');
    }
};
