<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('top_bar_enabled')->default(true);
            $table->string('top_bar_left_en')->nullable();
            $table->string('top_bar_left_ur')->nullable();
            $table->string('top_bar_center_en')->nullable();
            $table->string('top_bar_center_ur')->nullable();
            $table->string('top_bar_right_en')->nullable();
            $table->string('top_bar_right_ur')->nullable();

            $table->boolean('ticker_enabled')->default(true);
            $table->unsignedTinyInteger('ticker_speed')->default(40);

            $table->boolean('header_enabled')->default(true);
            $table->string('site_name_en')->nullable();
            $table->string('site_name_ur')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('search_placeholder_en')->nullable();
            $table->string('search_placeholder_ur')->nullable();
            $table->boolean('show_social')->default(true);
            $table->string('social_facebook')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_whatsapp')->nullable();
            $table->string('social_youtube')->nullable();
            $table->string('social_twitter')->nullable();
            $table->boolean('show_auth_links')->default(true);
            $table->string('login_url')->default('/admin/login');
            $table->string('register_url')->nullable();
            $table->boolean('show_cart')->default(true);

            $table->boolean('menu_enabled')->default(true);
            $table->boolean('menu_show_home')->default(true);
            $table->string('menu_home_label_en')->default('Home');
            $table->string('menu_home_label_ur')->nullable();

            $table->boolean('category_grid_enabled')->default(true);
            $table->unsignedTinyInteger('category_grid_columns')->default(5);
            $table->string('category_grid_title_en')->nullable();
            $table->string('category_grid_title_ur')->nullable();

            $table->timestamps();
        });

        Schema::create('store_ticker_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('text_en');
            $table->string('text_ur')->nullable();
            $table->string('link')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('store_menu_items', function (Blueprint $table): void {
            $table->id();
            $table->string('label_en');
            $table->string('label_ur')->nullable();
            $table->string('url')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('opens_in_new_tab')->default(false);
            $table->timestamps();
        });

        DB::table('store_settings')->insert([
            'top_bar_left_en' => 'Free delivery on orders above Rs. 5,000',
            'top_bar_left_ur' => '5,000 روپے سے زیادہ آرڈر پر مفت ترسیل',
            'top_bar_center_en' => 'Agricart — Agriculture & Farming Supplies',
            'top_bar_center_ur' => 'Agricart — زرعی اور فارمنگ سامان',
            'top_bar_right_en' => 'Call: +92 300 0000000',
            'top_bar_right_ur' => 'فون: +92 300 0000000',
            'site_name_en' => 'Agricart',
            'site_name_ur' => 'Agricart',
            'search_placeholder_en' => 'Search products, seeds, tools…',
            'search_placeholder_ur' => 'مصنوعات، بیج، اوزار تلاش کریں…',
            'category_grid_title_en' => 'Shop by Category',
            'category_grid_title_ur' => 'زمرے کے مطابق خریداری',
            'menu_home_label_ur' => 'ہوم',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_ticker_messages')->insert([
            [
                'text_en' => 'Welcome to Agricart Online Store — quality agriculture products delivered to your door.',
                'text_ur' => 'Agricart آن لائن سٹور میں خوش آمدید — معیاری زرعی مصنوعات آپ کے دروازے تک۔',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'text_en' => 'New season seeds and fertilizers now available.',
                'text_ur' => 'نئے موسم کے بیج اور کھادیں اب دستیاب ہیں۔',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_menu_items');
        Schema::dropIfExists('store_ticker_messages');
        Schema::dropIfExists('store_settings');
    }
};
