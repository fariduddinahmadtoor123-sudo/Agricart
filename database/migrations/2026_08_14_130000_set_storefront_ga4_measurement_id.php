<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_settings') || ! Schema::hasColumn('store_settings', 'ga_measurement_id')) {
            return;
        }

        DB::table('store_settings')->update([
            'ga_measurement_id' => 'G-JV0SHL1YGX',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('store_settings') || ! Schema::hasColumn('store_settings', 'ga_measurement_id')) {
            return;
        }

        DB::table('store_settings')
            ->where('ga_measurement_id', 'G-JV0SHL1YGX')
            ->update([
                'ga_measurement_id' => null,
                'updated_at' => now(),
            ]);
    }
};
