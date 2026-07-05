<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title_en');
            $table->string('title_ur')->nullable();
            $table->string('slug')->unique();
            $table->longText('content_en')->nullable();
            $table->longText('content_ur')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('top_bar_phone')->nullable()->after('top_bar_right_ur');
            $table->boolean('show_social_top_bar')->default(true)->after('top_bar_phone');
            $table->string('whatsapp_number')->nullable()->after('show_cart');
            $table->boolean('whatsapp_enabled')->default(true)->after('whatsapp_number');
            $table->string('whatsapp_message_en')->nullable()->after('whatsapp_enabled');
            $table->string('whatsapp_message_ur')->nullable()->after('whatsapp_message_en');
        });

        Schema::table('store_menu_items', function (Blueprint $table): void {
            $table->foreignId('store_page_id')->nullable()->after('category_id')->constrained('store_pages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('store_menu_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('store_page_id');
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'top_bar_phone',
                'show_social_top_bar',
                'whatsapp_number',
                'whatsapp_enabled',
                'whatsapp_message_en',
                'whatsapp_message_ur',
            ]);
        });

        Schema::dropIfExists('store_pages');
    }
};
