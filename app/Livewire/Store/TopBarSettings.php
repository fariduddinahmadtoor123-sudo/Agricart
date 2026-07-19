<?php

namespace App\Livewire\Store;

use Illuminate\Contracts\View\View;

class TopBarSettings extends BaseStoreSettings
{
    public bool $top_bar_enabled = true;

    public string $top_bar_left_en = '';

    public string $top_bar_left_ur = '';

    public string $top_bar_center_en = '';

    public string $top_bar_center_ur = '';

    public string $top_bar_right_en = '';

    public string $top_bar_right_ur = '';

    public string $top_bar_phone = '';

    public bool $show_social_top_bar = true;

    public function mount(): void
    {
        if (! $this->canManageStoreSettings()) {
            return;
        }

        $settings = $this->settings();

        $this->top_bar_enabled = $settings->top_bar_enabled;
        $this->top_bar_left_en = $settings->top_bar_left_en ?? '';
        $this->top_bar_left_ur = $settings->top_bar_left_ur ?? '';
        $this->top_bar_center_en = $settings->top_bar_center_en ?? '';
        $this->top_bar_center_ur = $settings->top_bar_center_ur ?? '';
        $this->top_bar_right_en = $settings->top_bar_right_en ?? '';
        $this->top_bar_right_ur = $settings->top_bar_right_ur ?? '';
        $this->top_bar_phone = $settings->top_bar_phone ?? '';
        $this->show_social_top_bar = $settings->show_social_top_bar;
    }

    public function save(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'top_bar_left_en' => ['nullable', 'string', 'max:500'],
            'top_bar_left_ur' => ['nullable', 'string', 'max:500'],
            'top_bar_center_en' => ['nullable', 'string', 'max:500'],
            'top_bar_center_ur' => ['nullable', 'string', 'max:500'],
            'top_bar_right_en' => ['nullable', 'string', 'max:500'],
            'top_bar_right_ur' => ['nullable', 'string', 'max:500'],
            'top_bar_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $settings = $this->settings();
        $settings->fill([
            'top_bar_enabled' => $this->top_bar_enabled,
            'top_bar_left_en' => $this->top_bar_left_en,
            'top_bar_left_ur' => $this->top_bar_left_ur,
            'top_bar_center_en' => $this->top_bar_center_en,
            'top_bar_center_ur' => $this->top_bar_center_ur,
            'top_bar_right_en' => $this->top_bar_right_en,
            'top_bar_right_ur' => $this->top_bar_right_ur,
            'top_bar_phone' => filled($this->top_bar_phone) ? $this->top_bar_phone : null,
            'show_social_top_bar' => $this->show_social_top_bar,
        ]);
        $settings->save();

        $this->flashSaved();
    }

    public function render(): View
    {
        return view('livewire.store.top-bar-settings');
    }
}
