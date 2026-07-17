<?php

namespace App\Livewire\Store;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class HeaderSettings extends BaseStoreSettings
{
    use WithFileUploads;

    public bool $header_enabled = true;

    public string $site_name_en = '';

    public string $site_name_ur = '';

    public $logoUpload = null;

    public ?string $logoPreviewUrl = null;

    public string $search_placeholder_en = '';

    public string $search_placeholder_ur = '';

    public bool $show_social = true;

    public string $social_facebook = '';

    public string $social_instagram = '';

    public string $social_whatsapp = '';

    public string $social_youtube = '';

    public string $social_twitter = '';

    public bool $show_auth_links = true;

    public string $login_url = '/login';

    public string $register_url = '/register';

    public bool $show_cart = true;

    public string $whatsapp_number = '';

    public bool $whatsapp_enabled = true;

    public string $whatsapp_message_en = '';

    public string $whatsapp_message_ur = '';

    public function mount(): void
    {
        $settings = $this->settings();

        $this->header_enabled = $settings->header_enabled;
        $this->site_name_en = $settings->site_name_en ?? '';
        $this->site_name_ur = $settings->site_name_ur ?? '';
        $this->logoPreviewUrl = filled($settings->logo_path)
            ? Storage::disk('public')->url($settings->logo_path)
            : null;
        $this->search_placeholder_en = $settings->search_placeholder_en ?? '';
        $this->search_placeholder_ur = $settings->search_placeholder_ur ?? '';
        $this->show_social = $settings->show_social;
        $this->social_facebook = $settings->social_facebook ?? '';
        $this->social_instagram = $settings->social_instagram ?? '';
        $this->social_whatsapp = $settings->social_whatsapp ?? '';
        $this->social_youtube = $settings->social_youtube ?? '';
        $this->social_twitter = $settings->social_twitter ?? '';
        $this->show_auth_links = $settings->show_auth_links;
        $this->login_url = $settings->login_url ?? '/login';
        $this->register_url = $settings->register_url ?? '';
        $this->show_cart = $settings->show_cart;
        $this->whatsapp_number = $settings->whatsapp_number ?? '';
        $this->whatsapp_enabled = $settings->whatsapp_enabled;
        $this->whatsapp_message_en = $settings->whatsapp_message_en ?? '';
        $this->whatsapp_message_ur = $settings->whatsapp_message_ur ?? '';
    }

    public function updatedLogoUpload(): void
    {
        $this->validate([
            'logoUpload' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->logoUpload === null) {
            return;
        }

        $path = app(\App\Services\Media\OptimizedImageStorage::class)->store($this->logoUpload, 'store');
        $settings = $this->settings();

        if (filled($settings->logo_path)) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $settings->update(['logo_path' => $path]);
        $this->logoPreviewUrl = Storage::disk('public')->url($path);
        $this->logoUpload = null;
        $this->flashSaved();
    }

    public function removeLogo(): void
    {
        $settings = $this->settings();

        if (filled($settings->logo_path)) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->update(['logo_path' => null]);
        }

        $this->logoPreviewUrl = null;
        $this->flashSaved();
    }

    public function save(): void
    {
        $this->validate([
            'site_name_en' => ['nullable', 'string', 'max:255'],
            'site_name_ur' => ['nullable', 'string', 'max:255'],
            'search_placeholder_en' => ['nullable', 'string', 'max:255'],
            'search_placeholder_ur' => ['nullable', 'string', 'max:255'],
            'social_facebook' => ['nullable', 'string', 'max:500'],
            'social_instagram' => ['nullable', 'string', 'max:500'],
            'social_whatsapp' => ['nullable', 'string', 'max:500'],
            'social_youtube' => ['nullable', 'string', 'max:500'],
            'social_twitter' => ['nullable', 'string', 'max:500'],
            'login_url' => ['nullable', 'string', 'max:500'],
            'register_url' => ['nullable', 'string', 'max:500'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_message_en' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_ur' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = $this->settings();
        $settings->fill([
            'header_enabled' => $this->header_enabled,
            'site_name_en' => $this->site_name_en,
            'site_name_ur' => $this->site_name_ur,
            'search_placeholder_en' => $this->search_placeholder_en,
            'search_placeholder_ur' => $this->search_placeholder_ur,
            'show_social' => $this->show_social,
            'social_facebook' => filled($this->social_facebook) ? $this->social_facebook : null,
            'social_instagram' => filled($this->social_instagram) ? $this->social_instagram : null,
            'social_whatsapp' => filled($this->social_whatsapp) ? $this->social_whatsapp : null,
            'social_youtube' => filled($this->social_youtube) ? $this->social_youtube : null,
            'social_twitter' => filled($this->social_twitter) ? $this->social_twitter : null,
            'show_auth_links' => $this->show_auth_links,
            'login_url' => filled($this->login_url) ? $this->login_url : '/login',
            'register_url' => filled($this->register_url) ? $this->register_url : null,
            'show_cart' => $this->show_cart,
            'whatsapp_number' => filled($this->whatsapp_number) ? $this->whatsapp_number : null,
            'whatsapp_enabled' => $this->whatsapp_enabled,
            'whatsapp_message_en' => filled($this->whatsapp_message_en) ? $this->whatsapp_message_en : null,
            'whatsapp_message_ur' => filled($this->whatsapp_message_ur) ? $this->whatsapp_message_ur : null,
        ]);
        $settings->save();

        $this->flashSaved();
    }

    public function render(): View
    {
        return view('livewire.store.header-settings');
    }
}
