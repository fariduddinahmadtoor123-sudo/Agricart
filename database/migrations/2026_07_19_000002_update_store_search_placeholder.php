<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_settings')) {
            return;
        }

        DB::table('store_settings')
            ->where('search_placeholder_en', 'Search products, seeds, tools…')
            ->update(['search_placeholder_en' => 'Search products, categories, brands, or pages…']);

        DB::table('store_settings')
            ->where('search_placeholder_ur', 'مصنوعات، بیج، اوزار تلاش کریں…')
            ->update(['search_placeholder_ur' => 'مصنوعات، زمرے، برانڈز یا صفحات تلاش کریں…']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('store_settings')) {
            return;
        }

        DB::table('store_settings')
            ->where('search_placeholder_en', 'Search products, categories, brands, or pages…')
            ->update(['search_placeholder_en' => 'Search products, seeds, tools…']);

        DB::table('store_settings')
            ->where('search_placeholder_ur', 'مصنوعات، زمرے، برانڈز یا صفحات تلاش کریں…')
            ->update(['search_placeholder_ur' => 'مصنوعات، بیج، اوزار تلاش کریں…']);
    }
};
