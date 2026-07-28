<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('image_original_path')->nullable()->after('image_path');
            $table->json('gallery_original_images')->nullable()->after('gallery_images');
            $table->unsignedInteger('images_watermark_version')->nullable()->after('gallery_original_images');
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->boolean('product_watermark_enabled')->default(true)->after('meta_pixel_id');
            $table->string('product_watermark_logo_path')->nullable()->after('product_watermark_enabled');
            $table->string('product_watermark_position', 32)->default('bottom-right')->after('product_watermark_logo_path');
            $table->unsignedTinyInteger('product_watermark_size_percent')->default(15)->after('product_watermark_position');
            $table->unsignedTinyInteger('product_watermark_opacity')->default(55)->after('product_watermark_size_percent');
            $table->unsignedInteger('product_watermark_version')->default(1)->after('product_watermark_opacity');
            $table->json('marketplace_image_preferences')->nullable()->after('product_watermark_version');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'image_original_path',
                'gallery_original_images',
                'images_watermark_version',
            ]);
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'product_watermark_enabled',
                'product_watermark_logo_path',
                'product_watermark_position',
                'product_watermark_size_percent',
                'product_watermark_opacity',
                'product_watermark_version',
                'marketplace_image_preferences',
            ]);
        });
    }
};
