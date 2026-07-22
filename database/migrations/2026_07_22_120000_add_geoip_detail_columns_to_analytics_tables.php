<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_visitors', function (Blueprint $table): void {
            $table->string('first_region_name')->nullable()->after('first_city');
            $table->string('first_timezone', 64)->nullable()->after('first_region_name');
        });

        Schema::table('analytics_sessions', function (Blueprint $table): void {
            $table->string('region_name')->nullable()->after('city')->index();
            $table->string('region_code', 16)->nullable()->after('region_name');
            $table->string('timezone', 64)->nullable()->after('region_code');
            $table->decimal('latitude', 10, 7)->nullable()->after('timezone');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('analytics_visitors', function (Blueprint $table): void {
            $table->dropColumn(['first_region_name', 'first_timezone']);
        });

        Schema::table('analytics_sessions', function (Blueprint $table): void {
            $table->dropColumn(['region_name', 'region_code', 'timezone', 'latitude', 'longitude']);
        });
    }
};
