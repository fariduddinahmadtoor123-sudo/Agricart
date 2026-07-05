<?php

namespace App\Livewire\Store;

use Illuminate\Contracts\View\View;

class OverviewSettings extends BaseStoreSettings
{
    public bool $category_grid_enabled = true;

    public int $category_grid_columns = 5;

    public string $category_grid_title_en = '';

    public string $category_grid_title_ur = '';

    public function mount(): void
    {
        $settings = $this->settings();

        $this->category_grid_enabled = $settings->category_grid_enabled;
        $this->category_grid_columns = $settings->category_grid_columns;
        $this->category_grid_title_en = $settings->category_grid_title_en ?? '';
        $this->category_grid_title_ur = $settings->category_grid_title_ur ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'category_grid_columns' => ['required', 'integer', 'min:3', 'max:6'],
            'category_grid_title_en' => ['nullable', 'string', 'max:255'],
            'category_grid_title_ur' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $this->settings();
        $settings->fill([
            'category_grid_enabled' => $this->category_grid_enabled,
            'category_grid_columns' => $this->category_grid_columns,
            'category_grid_title_en' => $this->category_grid_title_en,
            'category_grid_title_ur' => $this->category_grid_title_ur,
        ]);
        $settings->save();

        $this->flashSaved();
    }

    public function render(): View
    {
        return view('livewire.store.overview-settings');
    }
}
