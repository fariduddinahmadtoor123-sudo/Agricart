<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->boolean('hero_slider_enabled')->default(true)->after('menu_home_label_ur');
            $table->unsignedSmallInteger('hero_slider_interval')->default(5)->after('hero_slider_enabled');
        });

        Schema::create('store_hero_slides', function (Blueprint $table): void {
            $table->id();
            $table->string('image_path');
            $table->string('alt_en')->nullable();
            $table->string('alt_ur')->nullable();
            $table->string('link')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_hero_slides');

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn(['hero_slider_enabled', 'hero_slider_interval']);
        });
    }
};
