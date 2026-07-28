<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->string('image_original_path')->nullable()->after('image_path');
            $table->unsignedInteger('image_watermark_version')->nullable()->after('image_original_path');
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->boolean('brand_watermark_enabled')->default(true)->after('marketplace_image_preferences');
            $table->string('brand_watermark_logo_path')->nullable()->after('brand_watermark_enabled');
            $table->string('brand_watermark_text')->nullable()->after('brand_watermark_logo_path');
            $table->string('brand_watermark_position', 32)->default('bottom-right')->after('brand_watermark_text');
            $table->unsignedTinyInteger('brand_watermark_size_percent')->default(18)->after('brand_watermark_position');
            $table->unsignedTinyInteger('brand_watermark_opacity')->default(70)->after('brand_watermark_size_percent');
            $table->unsignedInteger('brand_watermark_version')->default(1)->after('brand_watermark_opacity');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn(['image_original_path', 'image_watermark_version']);
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'brand_watermark_enabled',
                'brand_watermark_logo_path',
                'brand_watermark_text',
                'brand_watermark_position',
                'brand_watermark_size_percent',
                'brand_watermark_opacity',
                'brand_watermark_version',
            ]);
        });
    }
};
