<?php

namespace App\Services\Store;

use App\Models\StoreHeroSlide;
use App\Models\StoreSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StoreHeroSliderService
{
    public function clearCache(): void
    {
        Cache::forget('store.hero_slides.active');
    }

    /**
     * @return Collection<int, StoreHeroSlide>
     */
    public function activeSlides(): Collection
    {
        $settings = StoreSetting::current();

        if (! $settings->hero_slider_enabled) {
            return collect();
        }

        return Cache::remember('store.hero_slides.active', now()->addMinutes(30), static fn (): Collection => StoreHeroSlide::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get());
    }

    public function intervalSeconds(): int
    {
        $interval = (int) (StoreSetting::current()->hero_slider_interval ?? 5);

        return max(3, min(30, $interval));
    }
}
