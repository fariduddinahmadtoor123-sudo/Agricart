<?php

use App\Models\StoreMenuItem;
use App\Models\StorePage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_about_settings', function (Blueprint $table): void {
            $table->id();
            $table->text('story_en')->nullable();
            $table->text('story_ur')->nullable();
            $table->string('hero_years_label')->nullable();
            $table->string('ceo_name')->nullable();
            $table->string('ceo_title_en')->nullable();
            $table->string('ceo_title_ur')->nullable();
            $table->text('ceo_bio_en')->nullable();
            $table->text('ceo_bio_ur')->nullable();
            $table->string('ceo_photo_path')->nullable();
            $table->json('office_images')->nullable();
            $table->json('team_members')->nullable();
            $table->timestamps();
        });

        $storyEn = null;
        $storyUr = null;

        if (Schema::hasTable('store_pages')) {
            $page = StorePage::query()->where('slug', 'about-us')->first();
            if ($page) {
                $storyEn = $page->content_en;
                $storyUr = $page->content_ur;
            }
        }

        DB::table('store_about_settings')->insert([
            'story_en' => $storyEn,
            'story_ur' => $storyUr,
            'hero_years_label' => '27 years of trust',
            'ceo_name' => null,
            'ceo_title_en' => 'CEO & Founder',
            'ceo_title_ur' => 'سی ای او اور بانی',
            'ceo_bio_en' => null,
            'ceo_bio_ur' => null,
            'ceo_photo_path' => null,
            'office_images' => json_encode([]),
            'team_members' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('store_menu_items')) {
            StoreMenuItem::query()
                ->where('url', 'like', '%/page/about-us%')
                ->each(function (StoreMenuItem $item): void {
                    $item->update([
                        'url' => preg_replace('#/page/about-us/?$#', '/about', (string) $item->url) ?: '/about',
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_about_settings');
    }
};
