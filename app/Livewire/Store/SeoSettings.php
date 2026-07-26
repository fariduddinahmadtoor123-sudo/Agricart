<?php

namespace App\Livewire\Store;

use App\Services\Media\OptimizedImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class SeoSettings extends BaseStoreSettings
{
    use WithFileUploads;

    public string $google_site_verification = '';

    public string $bing_site_verification = '';

    public string $ga_measurement_id = '';

    public string $gtm_container_id = '';

    public string $meta_pixel_id = '';

    public $ogImageUpload = null;

    public ?string $ogImagePreviewUrl = null;

    public function mount(): void
    {
        if (! $this->canManageStoreSettings()) {
            return;
        }

        $settings = $this->settings();

        $this->google_site_verification = $settings->google_site_verification ?? '';
        $this->bing_site_verification = $settings->bing_site_verification ?? '';
        $this->ga_measurement_id = $settings->ga_measurement_id ?? '';
        $this->gtm_container_id = $settings->gtm_container_id ?? '';
        $this->meta_pixel_id = $settings->meta_pixel_id ?? '';
        $this->ogImagePreviewUrl = filled($settings->default_og_image_path)
            ? Storage::disk('public')->url($settings->default_og_image_path)
            : null;
    }

    public function updatedOgImageUpload(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'ogImageUpload' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($this->ogImageUpload === null) {
            return;
        }

        $path = app(OptimizedImageStorage::class)->store($this->ogImageUpload, 'store');
        $settings = $this->settings();
        $settings->update(['default_og_image_path' => $path]);
        $this->ogImagePreviewUrl = Storage::disk('public')->url($path);
        $this->ogImageUpload = null;
        $this->flashSaved();
    }

    public function removeOgImage(): void
    {
        $this->authorizeStoreSettings();
        $this->settings()->update(['default_og_image_path' => null]);
        $this->ogImagePreviewUrl = null;
        $this->flashSaved();
    }

    public function save(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'google_site_verification' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]*$/'],
            'bing_site_verification' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]*$/'],
            'ga_measurement_id' => ['nullable', 'string', 'max:64', 'regex:/^(G-[A-Z0-9]+)?$/i'],
            'gtm_container_id' => ['nullable', 'string', 'max:64', 'regex:/^(GTM-[A-Z0-9]+)?$/i'],
            'meta_pixel_id' => ['nullable', 'string', 'max:64', 'regex:/^(\d+)?$/'],
        ]);

        $this->settings()->fill([
            'google_site_verification' => filled($this->google_site_verification) ? trim($this->google_site_verification) : null,
            'bing_site_verification' => filled($this->bing_site_verification) ? trim($this->bing_site_verification) : null,
            'ga_measurement_id' => filled($this->ga_measurement_id) ? strtoupper(trim($this->ga_measurement_id)) : null,
            'gtm_container_id' => filled($this->gtm_container_id) ? strtoupper(trim($this->gtm_container_id)) : null,
            'meta_pixel_id' => filled($this->meta_pixel_id) ? trim($this->meta_pixel_id) : null,
        ])->save();

        $this->flashSaved();
    }

    public function render(): View
    {
        return view('livewire.store.seo-settings');
    }
}
