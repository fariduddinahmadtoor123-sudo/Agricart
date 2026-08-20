<?php

namespace App\Services\Store;

use App\Models\StoreHeroSlide;
use App\Models\StoreSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StoreHeroSliderService
{
    /** Banner width × height in pixels — matches storefront container (72rem) at 3:1 ratio. */
    public const IMAGE_WIDTH = 1152;

    public const IMAGE_HEIGHT = 384;

    /** @var list<int> Interval step 1–5 → 0.5s … 2.5s */
    public const INTERVAL_STEPS = [1, 2, 3, 4, 5];

    public function clearCache(): void
    {
        Cache::forget('store.hero_slides.active_ids');
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

        $ids = Cache::remember('store.hero_slides.active_ids', now()->addMinutes(30), static function (): array {
            return StoreHeroSlide::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->all();
        });

        if (! is_array($ids) || $ids === []) {
            return collect();
        }

        return StoreHeroSlide::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function intervalMilliseconds(): int
    {
        $step = (int) (StoreSetting::current()->hero_slider_interval ?? 4);

        if (! in_array($step, self::INTERVAL_STEPS, true)) {
            $step = 4;
        }

        return $step * 500;
    }

    /**
     * @return array<int, string>
     */
    public function intervalOptions(): array
    {
        return [
            1 => '0.5',
            2 => '1',
            3 => '1.5',
            4 => '2',
            5 => '2.5',
        ];
    }
}
