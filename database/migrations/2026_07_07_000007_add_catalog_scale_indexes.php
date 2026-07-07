<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index('status', 'products_status_index');
            $table->index('created_at', 'products_created_at_index');
            $table->index(['status', 'created_at'], 'products_status_created_at_index');
            $table->index('brand_id', 'products_brand_id_index');
            $table->index('sku', 'products_sku_index');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('CREATE INDEX products_name_en_index ON products (name_en(191))');
            DB::statement('CREATE INDEX products_name_ur_index ON products (name_ur(191))');
        } else {
            Schema::table('products', function (Blueprint $table): void {
                $table->index('name_en', 'products_name_en_index');
                $table->index('name_ur', 'products_name_ur_index');
            });
        }

        Schema::table('product_display_category', function (Blueprint $table): void {
            $table->index('category_id', 'product_display_category_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_display_category', function (Blueprint $table): void {
            $table->dropIndex('product_display_category_category_id_index');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_status_index');
            $table->dropIndex('products_created_at_index');
            $table->dropIndex('products_status_created_at_index');
            $table->dropIndex('products_brand_id_index');
            $table->dropIndex('products_sku_index');
            $table->dropIndex('products_name_en_index');
            $table->dropIndex('products_name_ur_index');
        });
    }
};
