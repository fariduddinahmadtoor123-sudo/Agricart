<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_schedule_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('type', 20)->default('full');
            $table->string('frequency', 20)->default('weekly');
            $table->string('time', 5)->default('02:00');
            $table->unsignedTinyInteger('weekday')->default(0);
            $table->boolean('database_daily_enabled')->default(false);
            $table->string('database_daily_time', 5)->default('01:30');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_schedule_settings');
    }
};
