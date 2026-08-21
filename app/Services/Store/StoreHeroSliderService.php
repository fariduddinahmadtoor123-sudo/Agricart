<?php

namespace App\Services\Store;

use App\Models\StoreHeroSlide;
use App\Models\StoreSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StoreHeroSliderService
{
    /** Banner width × height in pixels — 3:1 ratio, sharp on desktop (≈72rem) and retina. */
    public const IMAGE_WIDTH = 1920;

    public const IMAGE_HEIGHT = 640;

    /** @var list<int> Interval step 1–10 → 0.5s … 5s */
    public const INTERVAL_STEPS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

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
            6 => '3',
            7 => '3.5',
            8 => '4',
            9 => '4.5',
            10 => '5',
        ];
    }

    /**
     * Move a slide to a 1-based position; the displaced slides shift to fill the gap.
     */
    public function moveToPosition(int $slideId, int $position): void
    {
        $slides = StoreHeroSlide::query()->orderBy('sort_order')->orderBy('id')->get();

        if ($slides->isEmpty()) {
            return;
        }

        $moving = $slides->firstWhere('id', $slideId);

        if ($moving === null) {
            return;
        }

        $position = max(1, min($slides->count(), $position));
        $ordered = $slides->reject(static fn (StoreHeroSlide $slide): bool => $slide->id === $slideId)->values();
        $ordered->splice($position - 1, 0, [$moving]);

        $this->writeSortOrders($ordered);
        $this->clearCache();
    }

    /**
     * Keep sort_order as contiguous 1…n after create/delete/reorder.
     */
    public function renumberSortOrders(): void
    {
        $slides = StoreHeroSlide::query()->orderBy('sort_order')->orderBy('id')->get();
        $this->writeSortOrders($slides);
        $this->clearCache();
    }

    /**
     * @param  Collection<int, StoreHeroSlide>  $ordered
     */
    private function writeSortOrders(Collection $ordered): void
    {
        foreach ($ordered->values() as $index => $slide) {
            $next = $index + 1;

            if ((int) $slide->sort_order !== $next) {
                $slide->update(['sort_order' => $next]);
            }
        }
    }
}
