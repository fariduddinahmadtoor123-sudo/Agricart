<?php

namespace Tests\Feature\Store;

use App\Models\StoreHeroSlide;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreHeroSliderTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_hero_slider_when_enabled_and_slides_exist(): void
    {
        Storage::fake('public');

        StoreSetting::current()->update([
            'hero_slider_enabled' => true,
            'hero_slider_interval' => 6,
        ]);

        StoreHeroSlide::query()->create([
            'image_path' => 'store/hero/banner-one.webp',
            'alt_en' => 'Season sale',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('data-store-hero-slider', false)
            ->assertSee('Season sale', false)
            ->assertSee('data-interval="6"', false);
    }

    public function test_products_page_does_not_show_hero_slider(): void
    {
        Storage::fake('public');

        StoreSetting::current()->update(['hero_slider_enabled' => true]);

        StoreHeroSlide::query()->create([
            'image_path' => 'store/hero/banner-one.webp',
            'alt_en' => 'Season sale',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('store.products'))
            ->assertOk()
            ->assertDontSee('data-store-hero-slider', false);
    }

    public function test_mobile_menu_uses_side_drawer_markup(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('data-store-menu-drawer', false)
            ->assertSee('data-store-menu-backdrop', false)
            ->assertSee('data-store-menu-close', false);
    }
}
