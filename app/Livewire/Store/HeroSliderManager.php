<?php

namespace App\Livewire\Store;

use App\Models\StoreHeroSlide;
use App\Rules\SafeUrl;
use App\Services\Media\OptimizedImageStorage;
use App\Services\Store\StoreHeroSliderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class HeroSliderManager extends BaseStoreSettings
{
    use WithFileUploads;

    public bool $hero_slider_enabled = true;

    public int $hero_slider_interval = 4;

    public $imageUpload = null;

    public string $alt_en = '';

    public string $alt_ur = '';

    public string $link = '';

    public ?int $editingId = null;

    public function mount(): void
    {
        if (! $this->canManageStoreSettings()) {
            return;
        }

        $settings = $this->settings();
        $this->hero_slider_enabled = $settings->hero_slider_enabled;
        $interval = (int) ($settings->hero_slider_interval ?? 4);
        $this->hero_slider_interval = in_array($interval, StoreHeroSliderService::INTERVAL_STEPS, true) ? $interval : 4;
    }

    public function saveSettings(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'hero_slider_interval' => ['required', 'integer', 'in:' . implode(',', StoreHeroSliderService::INTERVAL_STEPS)],
        ]);

        $settings = $this->settings();
        $settings->fill([
            'hero_slider_enabled' => $this->hero_slider_enabled,
            'hero_slider_interval' => $this->hero_slider_interval,
        ]);
        $settings->save();

        app(StoreHeroSliderService::class)->clearCache();
        $this->flashSaved();
    }

    public function saveSlide(): void
    {
        $this->authorizeStoreSettings();

        $rules = [
            'alt_en' => ['nullable', 'string', 'max:255'],
            'alt_ur' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:500', new SafeUrl],
        ];

        if (! $this->editingId) {
            $rules['imageUpload'] = ['required', 'image', 'max:4096'];
        } else {
            $rules['imageUpload'] = ['nullable', 'image', 'max:4096'];
        }

        $this->validate($rules);

        $payload = [
            'alt_en' => filled($this->alt_en) ? $this->alt_en : null,
            'alt_ur' => filled($this->alt_ur) ? $this->alt_ur : null,
            'link' => filled($this->link) ? $this->link : null,
        ];

        if ($this->editingId) {
            $slide = StoreHeroSlide::query()->findOrFail($this->editingId);
            $slide->update($payload);

            if ($this->imageUpload !== null) {
                $this->replaceSlideImage($slide);
            }
        } else {
            $path = app(OptimizedImageStorage::class)->store($this->imageUpload, 'store/hero');
            $maxSort = (int) StoreHeroSlide::query()->max('sort_order');

            StoreHeroSlide::query()->create([
                ...$payload,
                'image_path' => $path,
                'sort_order' => $maxSort + 1,
                'is_active' => true,
            ]);

            app(StoreHeroSliderService::class)->renumberSortOrders();
        }

        $this->resetSlideForm();
        app(StoreHeroSliderService::class)->clearCache();
        $this->flashSaved();
    }

    public function editSlide(int $id): void
    {
        $this->authorizeStoreSettings();
        $slide = StoreHeroSlide::query()->findOrFail($id);

        $this->editingId = $slide->id;
        $this->alt_en = $slide->alt_en ?? '';
        $this->alt_ur = $slide->alt_ur ?? '';
        $this->link = $slide->link ?? '';
        $this->imageUpload = null;

        $this->js('document.getElementById("store-hero-slide-form")?.scrollIntoView({behavior:"smooth",block:"start"})');
    }

    public function deleteSlide(int $id): void
    {
        $this->authorizeStoreSettings();
        $slide = StoreHeroSlide::query()->findOrFail($id);
        $this->deleteSlideImage($slide);
        $slide->delete();

        if ($this->editingId === $id) {
            $this->resetSlideForm();
        }

        app(StoreHeroSliderService::class)->renumberSortOrders();
        $this->flashSaved();
    }

    public function toggleSlide(int $id): void
    {
        $this->authorizeStoreSettings();
        $slide = StoreHeroSlide::query()->findOrFail($id);
        $slide->update(['is_active' => ! $slide->is_active]);

        app(StoreHeroSliderService::class)->clearCache();
        $this->flashSaved();
    }

    public function moveSlide(int $id, string $direction): void
    {
        $this->authorizeStoreSettings();
        $slides = StoreHeroSlide::query()->orderBy('sort_order')->orderBy('id')->get();
        $index = $slides->search(fn (StoreHeroSlide $slide): bool => $slide->id === $id);

        if ($index === false) {
            return;
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex < 0 || $swapIndex >= $slides->count()) {
            return;
        }

        app(StoreHeroSliderService::class)->moveToPosition($id, $swapIndex + 1);
        $this->flashSaved();
    }

    public function setSlideNumber(int $id, $number): void
    {
        $this->authorizeStoreSettings();

        $position = (int) $number;

        if ($position < 1) {
            return;
        }

        app(StoreHeroSliderService::class)->moveToPosition($id, $position);
        $this->flashSaved();
    }

    public function resetSlideForm(): void
    {
        $this->authorizeStoreSettings();
        $this->editingId = null;
        $this->imageUpload = null;
        $this->alt_en = '';
        $this->alt_ur = '';
        $this->link = '';
    }

    public function render(): View
    {
        return view('livewire.store.hero-slider-manager', [
            'slides' => $this->canManageStoreSettings()
                ? StoreHeroSlide::query()->orderBy('sort_order')->orderBy('id')->get()
                : collect(),
            'intervalOptions' => app(StoreHeroSliderService::class)->intervalOptions(),
            'recommendedSize' => StoreHeroSliderService::IMAGE_WIDTH . ' × ' . StoreHeroSliderService::IMAGE_HEIGHT,
        ]);
    }

    private function replaceSlideImage(StoreHeroSlide $slide): void
    {
        if ($this->imageUpload === null) {
            return;
        }

        $this->deleteSlideImage($slide);
        $path = app(OptimizedImageStorage::class)->store($this->imageUpload, 'store/hero');
        $slide->update(['image_path' => $path]);
        $this->imageUpload = null;
    }

    private function deleteSlideImage(StoreHeroSlide $slide): void
    {
        if (! filled($slide->image_path)) {
            return;
        }

        Storage::disk('public')->delete($slide->image_path);
    }
}
