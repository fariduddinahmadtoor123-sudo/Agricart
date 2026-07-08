<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_channels', function (Blueprint $table): void {
            $table->boolean('maintenance_mode')->default(false)->after('is_enabled');
            $table->string('maintenance_message_en')->nullable()->after('banner_message_ur');
            $table->string('maintenance_message_ur')->nullable()->after('maintenance_message_en');
        });
    }

    public function down(): void
    {
        Schema::table('sales_channels', function (Blueprint $table): void {
            $table->dropColumn([
                'maintenance_mode',
                'maintenance_message_en',
                'maintenance_message_ur',
            ]);
        });
    }
};
