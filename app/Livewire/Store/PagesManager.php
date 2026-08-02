<?php

namespace App\Livewire\Store;

use App\Models\StorePage;
use App\Services\Store\StoreAboutService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class PagesManager extends BaseStoreSettings
{
    public string $title_en = '';

    public string $title_ur = '';

    public string $slug = '';

    public string $content_en = '';

    public string $content_ur = '';

    public bool $is_active = true;

    public bool $show_in_menu = false;

    public bool $show_in_footer = false;

    public bool $show_in_legal = false;

    public bool $show_in_quick_links = false;

    public bool $show_in_registration = false;

    public bool $showSlugField = false;

    public bool $slugManuallyEdited = false;

    public ?int $editingId = null;

    public function updatedTitleEn(string $value): void
    {
        $this->authorizeStoreSettings();

        if ($this->editingId !== null || $this->slugManuallyEdited) {
            return;
        }

        $this->slug = Str::slug($value);
    }

    public function updatedSlug(): void
    {
        $this->authorizeStoreSettings();
        $this->slugManuallyEdited = true;
    }

    public function updatedShowInFooter(bool $value): void
    {
        $this->authorizeStoreSettings();

        if ($value) {
            $this->show_in_legal = false;
        }
    }

    public function updatedShowInLegal(bool $value): void
    {
        $this->authorizeStoreSettings();

        if ($value) {
            $this->show_in_footer = false;
        }
    }

    public function getPreviewSlugProperty(): string
    {
        $base = filled(trim($this->slug))
            ? Str::slug($this->slug)
            : Str::slug($this->title_en);

        return filled($base) ? $base : '…';
    }

    public function savePage(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_ur' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content_en' => ['nullable', 'string'],
            'content_ur' => ['nullable', 'string'],
        ]);

        $normalized = filled(trim($this->slug))
            ? Str::slug($this->slug)
            : Str::slug($this->title_en);

        if (! filled($normalized)) {
            $this->addError('title_en', __('agricart.store.admin.pages.slug_from_title'));

            return;
        }

        $slug = StorePage::uniqueSlug($normalized, $this->editingId);

        $payload = [
            'title_en' => $this->title_en,
            'title_ur' => filled($this->title_ur) ? $this->title_ur : null,
            'slug' => $slug,
            'content_en' => $this->content_en,
            'content_ur' => filled($this->content_ur) ? $this->content_ur : null,
            'is_active' => $this->is_active,
            'show_in_menu' => $this->show_in_menu,
            'show_in_footer' => $this->show_in_footer,
            'show_in_legal' => $this->show_in_legal,
            'show_in_quick_links' => $this->show_in_quick_links,
            'show_in_registration' => $this->show_in_registration,
        ];

        if ($this->editingId) {
            StorePage::query()->whereKey($this->editingId)->update($payload);
            $page = StorePage::query()->find($this->editingId);
        } else {
            $maxSort = (int) StorePage::query()->max('sort_order');
            $page = StorePage::query()->create([
                ...$payload,
                'sort_order' => $maxSort + 1,
            ]);
        }

        if ($page instanceof StorePage) {
            app(StoreAboutService::class)->syncStoryFromCmsPage($page);
        }

        $this->resetPageForm();
        $this->flashSaved();
    }

    public function editPage(int $id): void
    {
        $this->authorizeStoreSettings();
        $page = StorePage::query()->findOrFail($id);

        $this->editingId = $page->id;
        $this->title_en = $page->title_en;
        $this->title_ur = $page->title_ur ?? '';
        $this->slug = $page->slug;
        $this->content_en = $page->content_en ?? '';
        $this->content_ur = $page->content_ur ?? '';
        $this->is_active = $page->is_active;
        $this->show_in_menu = $page->show_in_menu;
        $this->show_in_footer = $page->show_in_footer;
        $this->show_in_legal = $page->show_in_legal;
        $this->show_in_quick_links = $page->show_in_quick_links;
        $this->show_in_registration = $page->show_in_registration;
        $this->showSlugField = false;
        $this->slugManuallyEdited = true;
    }

    public function deletePage(int $id): void
    {
        $this->authorizeStoreSettings();
        StorePage::query()->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->resetPageForm();
        }

        $this->flashSaved();
    }

    public function togglePage(int $id): void
    {
        $this->authorizeStoreSettings();
        $page = StorePage::query()->findOrFail($id);
        $page->update(['is_active' => ! $page->is_active]);
        $this->flashSaved();
    }

    public function resetPageForm(): void
    {
        $this->authorizeStoreSettings();
        $this->editingId = null;
        $this->title_en = '';
        $this->title_ur = '';
        $this->slug = '';
        $this->content_en = '';
        $this->content_ur = '';
        $this->is_active = true;
        $this->show_in_menu = false;
        $this->show_in_footer = false;
        $this->show_in_legal = false;
        $this->show_in_quick_links = false;
        $this->show_in_registration = false;
        $this->showSlugField = false;
        $this->slugManuallyEdited = false;
    }

    public function render(): View
    {
        return view('livewire.store.pages-manager', [
            'pages' => $this->canManageStoreSettings()
                ? StorePage::query()->orderBy('sort_order')->orderBy('id')->get()
                : collect(),
        ]);
    }
}
