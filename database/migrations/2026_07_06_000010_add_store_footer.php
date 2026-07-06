<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->boolean('footer_enabled')->default(true)->after('category_grid_title_ur');
            $table->string('footer_tagline_en')->nullable()->after('footer_enabled');
            $table->string('footer_tagline_ur')->nullable()->after('footer_tagline_en');
            $table->string('footer_logo_path')->nullable()->after('footer_tagline_ur');
            $table->boolean('footer_show_logo')->default(true)->after('footer_logo_path');
            $table->string('footer_quick_title_en')->nullable()->after('footer_show_logo');
            $table->string('footer_quick_title_ur')->nullable()->after('footer_quick_title_en');
            $table->string('footer_policies_title_en')->nullable()->after('footer_quick_title_ur');
            $table->string('footer_policies_title_ur')->nullable()->after('footer_policies_title_en');
            $table->string('footer_registration_title_en')->nullable()->after('footer_policies_title_ur');
            $table->string('footer_registration_title_ur')->nullable()->after('footer_registration_title_en');
            $table->text('footer_map_embed')->nullable()->after('footer_registration_title_ur');
            $table->string('footer_copyright_en')->nullable()->after('footer_map_embed');
            $table->string('footer_copyright_ur')->nullable()->after('footer_copyright_en');
            $table->boolean('footer_show_social')->default(true)->after('footer_copyright_ur');
            $table->boolean('footer_show_login_link')->default(true)->after('footer_show_social');
            $table->boolean('footer_show_register_link')->default(true)->after('footer_show_login_link');
        });

        Schema::create('store_footer_links', function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('label_en');
            $table->string('label_ur')->nullable();
            $table->string('url')->nullable();
            $table->foreignId('store_page_id')->nullable()->constrained('store_pages')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('opens_in_new_tab')->default(false);
            $table->timestamps();
        });

        DB::table('store_settings')->update([
            'footer_tagline_en' => 'Buy & Sell Agriculture — Your trusted agriculture experience in Pakistan.',
            'footer_tagline_ur' => 'زراعت خرید و فروخت — پاکستان میں آپ کا قابلِ اعتماد زرعی تجربہ۔',
            'footer_quick_title_en' => 'Quick Links',
            'footer_quick_title_ur' => 'فوری لنکس',
            'footer_policies_title_en' => 'Policies',
            'footer_policies_title_ur' => 'پالیسیاں',
            'footer_registration_title_en' => 'Registration',
            'footer_registration_title_ur' => 'رجسٹریشن',
            'footer_copyright_en' => '© {year} Agricart. All rights reserved.',
            'footer_copyright_ur' => '© {year} Agricart. جملہ حقوق محفوظ ہیں۔',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_footer_links');

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'footer_enabled',
                'footer_tagline_en',
                'footer_tagline_ur',
                'footer_logo_path',
                'footer_show_logo',
                'footer_quick_title_en',
                'footer_quick_title_ur',
                'footer_policies_title_en',
                'footer_policies_title_ur',
                'footer_registration_title_en',
                'footer_registration_title_ur',
                'footer_map_embed',
                'footer_copyright_en',
                'footer_copyright_ur',
                'footer_show_social',
                'footer_show_login_link',
                'footer_show_register_link',
            ]);
        });
    }
};
