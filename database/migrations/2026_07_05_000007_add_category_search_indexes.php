<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('CREATE INDEX categories_name_en_index ON categories (name_en(191))');
            DB::statement('CREATE INDEX categories_name_ur_index ON categories (name_ur(191))');
        } else {
            Schema::table('categories', function (Blueprint $table): void {
                $table->index('name_en', 'categories_name_en_index');
                $table->index('name_ur', 'categories_name_ur_index');
            });
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->index('status', 'categories_status_index');
            $table->index(['status', 'created_at'], 'categories_status_created_at_index');
        });

        Schema::table('category_display', function (Blueprint $table): void {
            $table->index('display_category_id', 'category_display_display_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_name_en_index');
            $table->dropIndex('categories_name_ur_index');
            $table->dropIndex('categories_status_index');
            $table->dropIndex('categories_status_created_at_index');
        });

        Schema::table('category_display', function (Blueprint $table): void {
            $table->dropIndex('category_display_display_category_id_index');
        });
    }
};
