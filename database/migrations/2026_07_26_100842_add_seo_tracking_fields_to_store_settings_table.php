<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('google_site_verification', 255)->nullable();
            $table->string('bing_site_verification', 255)->nullable();
            $table->string('default_og_image_path', 500)->nullable();
            $table->string('ga_measurement_id', 64)->nullable();
            $table->string('gtm_container_id', 64)->nullable();
            $table->string('meta_pixel_id', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'google_site_verification',
                'bing_site_verification',
                'default_og_image_path',
                'ga_measurement_id',
                'gtm_container_id',
                'meta_pixel_id',
            ]);
        });
    }
};
