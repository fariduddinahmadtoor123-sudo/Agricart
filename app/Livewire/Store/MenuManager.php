<?php

namespace App\Livewire\Store;

use App\Models\Category;
use App\Models\StoreMenuItem;
use Illuminate\Contracts\View\View;

class MenuManager extends BaseStoreSettings
{
    public bool $menu_enabled = true;

    public bool $menu_show_home = true;

    public string $menu_home_label_en = 'Home';

    public string $menu_home_label_ur = '';

    public string $label_en = '';

    public string $label_ur = '';

    public string $url = '';

    public ?int $category_id = null;

    public ?int $store_page_id = null;

    public bool $opens_in_new_tab = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $settings = $this->settings();

        $this->menu_enabled = $settings->menu_enabled;
        $this->menu_show_home = $settings->menu_show_home;
        $this->menu_home_label_en = $settings->menu_home_label_en ?? 'Home';
        $this->menu_home_label_ur = $settings->menu_home_label_ur ?? '';
    }

    public function saveSettings(): void
    {
        $this->validate([
            'menu_home_label_en' => ['nullable', 'string', 'max:255'],
            'menu_home_label_ur' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $this->settings();
        $settings->fill([
            'menu_enabled' => $this->menu_enabled,
            'menu_show_home' => $this->menu_show_home,
            'menu_home_label_en' => filled($this->menu_home_label_en) ? $this->menu_home_label_en : 'Home',
            'menu_home_label_ur' => $this->menu_home_label_ur,
        ]);
        $settings->save();

        $this->flashSaved();
    }

    public function saveItem(): void
    {
        $this->validate([
            'label_en' => ['required', 'string', 'max:255'],
            'label_ur' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'store_page_id' => ['nullable', 'integer', 'exists:store_pages,id'],
        ]);

        if (! filled($this->url) && ! filled($this->category_id) && ! filled($this->store_page_id)) {
            $this->addError('url', __('agricart.store.admin.menu.url_or_target'));

            return;
        }

        $payload = [
            'label_en' => $this->label_en,
            'label_ur' => filled($this->label_ur) ? $this->label_ur : null,
            'url' => filled($this->url) ? $this->url : null,
            'category_id' => $this->category_id,
            'store_page_id' => $this->store_page_id,
            'opens_in_new_tab' => $this->opens_in_new_tab,
        ];

        if ($this->editingId) {
            StoreMenuItem::query()->whereKey($this->editingId)->update($payload);
        } else {
            $maxSort = (int) StoreMenuItem::query()->max('sort_order');
            StoreMenuItem::query()->create([
                ...$payload,
                'sort_order' => $maxSort + 1,
                'is_active' => true,
            ]);
        }

        $this->resetItemForm();
        $this->flashSaved();
    }

    public function editItem(int $id): void
    {
        $item = StoreMenuItem::query()->findOrFail($id);

        $this->editingId = $item->id;
        $this->label_en = $item->label_en;
        $this->label_ur = $item->label_ur ?? '';
        $this->url = $item->url ?? '';
        $this->category_id = $item->category_id;
        $this->store_page_id = $item->store_page_id;
        $this->opens_in_new_tab = $item->opens_in_new_tab;
    }

    public function deleteItem(int $id): void
    {
        StoreMenuItem::query()->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->resetItemForm();
        }

        $this->flashSaved();
    }

    public function toggleItem(int $id): void
    {
        $item = StoreMenuItem::query()->findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);
        $this->flashSaved();
    }

    public function moveItem(int $id, string $direction): void
    {
        $items = StoreMenuItem::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $index = $items->search(static fn (StoreMenuItem $item): bool => $item->id === $id);

        if ($index === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($targetIndex < 0 || $targetIndex >= $items->count()) {
            return;
        }

        $current = $items[$index];
        $other = $items[$targetIndex];
        $currentSort = $current->sort_order;

        $current->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $currentSort]);

        $this->flashSaved();
    }

    public function resetItemForm(): void
    {
        $this->editingId = null;
        $this->label_en = '';
        $this->label_ur = '';
        $this->url = '';
        $this->category_id = null;
        $this->store_page_id = null;
        $this->opens_in_new_tab = false;
    }

    public function render(): View
    {
        return view('livewire.store.menu-manager', [
            'items' => StoreMenuItem::query()->with(['category:id,name_en,slug', 'storePage:id,title_en,slug'])->orderBy('sort_order')->orderBy('id')->get(),
            'categories' => Category::query()->where('status', 'active')->orderBy('name_en')->limit(200)->get(['id', 'name_en', 'slug']),
            'pages' => \App\Models\StorePage::query()->where('is_active', true)->orderBy('title_en')->get(['id', 'title_en', 'slug']),
        ]);
    }
}
