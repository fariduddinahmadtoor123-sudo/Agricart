<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='quantity_channel_tiers'");

        foreach ($indexes as $index) {
            $name = $index->name;

            if (str_contains($name, 'min_quantity')) {
                DB::statement('DROP INDEX IF EXISTS ' . $name);
            }
        }
    }

    public function down(): void
    {
        // No-op: duplicate min quantities are allowed by design.
    }
};
