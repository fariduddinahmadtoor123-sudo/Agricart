<?php

namespace Tests\Feature\Store;

use App\Models\StoreHeroSlide;
use App\Models\StoreSetting;
use App\Services\Store\StoreHeroSliderService;
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
            'hero_slider_interval' => 4,
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
            ->assertSee('data-interval="2000"', false);
    }

    public function test_active_slides_reads_cached_ids_without_type_error(): void
    {
        StoreSetting::current()->update(['hero_slider_enabled' => true]);

        $slide = StoreHeroSlide::query()->create([
            'image_path' => 'store/hero/banner-one.webp',
            'alt_en' => 'Cached slide',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $service = app(StoreHeroSliderService::class);
        $service->clearCache();

        $first = $service->activeSlides();
        $this->assertCount(1, $first);
        $this->assertTrue($first->first()->is($slide));

        $cached = $service->activeSlides();
        $this->assertCount(1, $cached);
        $this->assertSame($slide->id, $cached->first()->id);
    }

    public function test_interval_milliseconds_uses_ten_step_presets(): void
    {
        StoreSetting::current()->update(['hero_slider_interval' => 1]);
        $this->assertSame(500, app(StoreHeroSliderService::class)->intervalMilliseconds());

        StoreSetting::current()->update(['hero_slider_interval' => 5]);
        $this->assertSame(2500, app(StoreHeroSliderService::class)->intervalMilliseconds());

        StoreSetting::current()->update(['hero_slider_interval' => 10]);
        $this->assertSame(5000, app(StoreHeroSliderService::class)->intervalMilliseconds());
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

    public function test_desktop_menu_does_not_show_mobile_toggle(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('ag-store-menu__desktop', false)
            ->assertSee('data-store-menu-drawer', false);
    }

    public function test_mobile_menu_uses_side_drawer_markup(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('data-store-menu-drawer', false)
            ->assertSee('data-store-menu-backdrop', false)
            ->assertSee('data-store-menu-close', false)
            ->assertSee('ag-store-header__menu-toggle', false);
    }
}
