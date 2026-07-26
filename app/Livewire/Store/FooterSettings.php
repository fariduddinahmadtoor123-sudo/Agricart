<?php

namespace App\Livewire\Store;

use App\Models\Category;
use App\Models\StoreFooterLink;
use App\Models\StorePage;
use App\Rules\SafeUrl;
use App\Services\Media\OptimizedImageStorage;
use App\Support\MapEmbedSanitizer;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class FooterSettings extends BaseStoreSettings
{
    use WithFileUploads;

    public bool $footer_enabled = true;

    public string $footer_tagline_en = '';

    public string $footer_tagline_ur = '';

    public bool $footer_show_logo = true;

    public $footerLogoUpload = null;

    public ?string $footerLogoPreviewUrl = null;

    public string $footer_quick_title_en = '';

    public string $footer_quick_title_ur = '';

    public string $footer_policies_title_en = '';

    public string $footer_policies_title_ur = '';

    public string $footer_legal_title_en = '';

    public string $footer_legal_title_ur = '';

    public string $footer_registration_title_en = '';

    public string $footer_registration_title_ur = '';

    public string $footer_map_embed = '';

    public string $footer_copyright_en = '';

    public string $footer_copyright_ur = '';

    public bool $footer_show_social = true;

    public bool $footer_show_login_link = true;

    public bool $footer_show_register_link = true;

    public string $link_group = StoreFooterLink::GROUP_QUICK;

    public string $label_en = '';

    public string $label_ur = '';

    public string $url = '';

    public ?int $category_id = null;

    public ?int $store_page_id = null;

    public bool $opens_in_new_tab = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        if (! $this->canManageStoreSettings()) {
            return;
        }

        $settings = $this->settings();

        $this->footer_enabled = $settings->footer_enabled;
        $this->footer_tagline_en = $settings->footer_tagline_en ?? '';
        $this->footer_tagline_ur = $settings->footer_tagline_ur ?? '';
        $this->footer_show_logo = $settings->footer_show_logo;
        $this->footerLogoPreviewUrl = filled($settings->footer_logo_path)
            ? Storage::disk('public')->url($settings->footer_logo_path)
            : null;
        $this->footer_quick_title_en = $settings->footer_quick_title_en ?? '';
        $this->footer_quick_title_ur = $settings->footer_quick_title_ur ?? '';
        $this->footer_policies_title_en = $settings->footer_policies_title_en ?? '';
        $this->footer_policies_title_ur = $settings->footer_policies_title_ur ?? '';
        $this->footer_legal_title_en = $settings->footer_legal_title_en ?? '';
        $this->footer_legal_title_ur = $settings->footer_legal_title_ur ?? '';
        $this->footer_registration_title_en = $settings->footer_registration_title_en ?? '';
        $this->footer_registration_title_ur = $settings->footer_registration_title_ur ?? '';
        $this->footer_map_embed = $settings->footer_map_embed ?? '';
        $this->footer_copyright_en = $settings->footer_copyright_en ?? '';
        $this->footer_copyright_ur = $settings->footer_copyright_ur ?? '';
        $this->footer_show_social = $settings->footer_show_social;
        $this->footer_show_login_link = $settings->footer_show_login_link;
        $this->footer_show_register_link = $settings->footer_show_register_link;
    }

    public function updatedFooterLogoUpload(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'footerLogoUpload' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->footerLogoUpload === null) {
            return;
        }

        $path = app(OptimizedImageStorage::class)->store($this->footerLogoUpload, 'store');
        $settings = $this->settings();

        $settings->update(['footer_logo_path' => $path]);
        $this->footerLogoPreviewUrl = Storage::disk('public')->url($path);
        $this->footerLogoUpload = null;
        $this->flashSaved();
    }

    public function removeFooterLogo(): void
    {
        $this->authorizeStoreSettings();
        $settings = $this->settings();

        if (filled($settings->footer_logo_path)) {
            $settings->update(['footer_logo_path' => null]);
        }

        $this->footerLogoPreviewUrl = null;
        $this->flashSaved();
    }

    public function saveSettings(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'footer_tagline_en' => ['nullable', 'string', 'max:500'],
            'footer_tagline_ur' => ['nullable', 'string', 'max:500'],
            'footer_quick_title_en' => ['nullable', 'string', 'max:255'],
            'footer_quick_title_ur' => ['nullable', 'string', 'max:255'],
            'footer_policies_title_en' => ['nullable', 'string', 'max:255'],
            'footer_policies_title_ur' => ['nullable', 'string', 'max:255'],
            'footer_legal_title_en' => ['nullable', 'string', 'max:255'],
            'footer_legal_title_ur' => ['nullable', 'string', 'max:255'],
            'footer_registration_title_en' => ['nullable', 'string', 'max:255'],
            'footer_registration_title_ur' => ['nullable', 'string', 'max:255'],
            'footer_map_embed' => [
                'nullable',
                'string',
                'max:5000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    if (MapEmbedSanitizer::sanitize($value) === null) {
                        $fail(__('agricart.store.admin.footer.map_embed_invalid'));
                    }
                },
            ],
            'footer_copyright_en' => ['nullable', 'string', 'max:500'],
            'footer_copyright_ur' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = $this->settings();
        $settings->fill([
            'footer_enabled' => $this->footer_enabled,
            'footer_tagline_en' => filled($this->footer_tagline_en) ? $this->footer_tagline_en : null,
            'footer_tagline_ur' => filled($this->footer_tagline_ur) ? $this->footer_tagline_ur : null,
            'footer_show_logo' => $this->footer_show_logo,
            'footer_quick_title_en' => filled($this->footer_quick_title_en) ? $this->footer_quick_title_en : null,
            'footer_quick_title_ur' => filled($this->footer_quick_title_ur) ? $this->footer_quick_title_ur : null,
            'footer_policies_title_en' => filled($this->footer_policies_title_en) ? $this->footer_policies_title_en : null,
            'footer_policies_title_ur' => filled($this->footer_policies_title_ur) ? $this->footer_policies_title_ur : null,
            'footer_legal_title_en' => filled($this->footer_legal_title_en) ? $this->footer_legal_title_en : null,
            'footer_legal_title_ur' => filled($this->footer_legal_title_ur) ? $this->footer_legal_title_ur : null,
            'footer_registration_title_en' => filled($this->footer_registration_title_en) ? $this->footer_registration_title_en : null,
            'footer_registration_title_ur' => filled($this->footer_registration_title_ur) ? $this->footer_registration_title_ur : null,
            'footer_map_embed' => filled($this->footer_map_embed)
                ? MapEmbedSanitizer::sanitize($this->footer_map_embed)
                : null,
            'footer_copyright_en' => filled($this->footer_copyright_en) ? $this->footer_copyright_en : null,
            'footer_copyright_ur' => filled($this->footer_copyright_ur) ? $this->footer_copyright_ur : null,
            'footer_show_social' => $this->footer_show_social,
            'footer_show_login_link' => $this->footer_show_login_link,
            'footer_show_register_link' => $this->footer_show_register_link,
        ]);
        $settings->save();

        $this->flashSaved();
    }

    public function saveLink(): void
    {
        $this->authorizeStoreSettings();
        $this->validate([
            'link_group' => ['required', 'in:'.StoreFooterLink::GROUP_QUICK.','.StoreFooterLink::GROUP_REGISTRATION],
            'label_en' => ['required', 'string', 'max:255'],
            'label_ur' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'store_page_id' => ['nullable', 'integer', 'exists:store_pages,id'],
        ]);

        if (! filled($this->url) && ! filled($this->category_id) && ! filled($this->store_page_id)) {
            $this->addError('url', __('agricart.store.admin.menu.url_or_target'));

            return;
        }

        if (filled($this->store_page_id)) {
            $duplicatePage = StoreFooterLink::query()
                ->where('group', $this->link_group)
                ->where('store_page_id', $this->store_page_id)
                ->when($this->editingId, fn ($query) => $query->whereKeyNot($this->editingId))
                ->exists();

            if ($duplicatePage) {
                $this->addError('store_page_id', __('agricart.store.admin.footer.duplicate_page'));

                return;
            }
        }

        $payload = [
            'group' => $this->link_group,
            'label_en' => $this->label_en,
            'label_ur' => filled($this->label_ur) ? $this->label_ur : null,
            'url' => filled($this->url) ? $this->url : null,
            'category_id' => $this->category_id,
            'store_page_id' => $this->store_page_id,
            'opens_in_new_tab' => $this->opens_in_new_tab,
        ];

        if ($this->editingId) {
            StoreFooterLink::query()->whereKey($this->editingId)->update($payload);
        } else {
            $maxSort = (int) StoreFooterLink::query()
                ->where('group', $this->link_group)
                ->max('sort_order');

            StoreFooterLink::query()->create([
                ...$payload,
                'sort_order' => $maxSort + 1,
                'is_active' => true,
            ]);
        }

        $this->resetLinkForm();
        $this->flashSaved();
    }

    public function editLink(int $id): void
    {
        $this->authorizeStoreSettings();
        $link = StoreFooterLink::query()->findOrFail($id);

        $this->editingId = $link->id;
        $this->link_group = $link->group;
        $this->label_en = $link->label_en;
        $this->label_ur = $link->label_ur ?? '';
        $this->url = $link->url ?? '';
        $this->category_id = $link->category_id;
        $this->store_page_id = $link->store_page_id;
        $this->opens_in_new_tab = $link->opens_in_new_tab;
    }

    public function deleteLink(int $id): void
    {
        $this->authorizeStoreSettings();
        StoreFooterLink::query()->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->resetLinkForm();
        }

        $this->flashSaved();
    }

    public function toggleLink(int $id): void
    {
        $this->authorizeStoreSettings();
        $link = StoreFooterLink::query()->findOrFail($id);
        $link->update(['is_active' => ! $link->is_active]);
        $this->flashSaved();
    }

    public function moveLink(int $id, string $direction): void
    {
        $this->authorizeStoreSettings();
        $link = StoreFooterLink::query()->findOrFail($id);

        $links = StoreFooterLink::query()
            ->where('group', $link->group)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $index = $links->search(static fn (StoreFooterLink $item): bool => $item->id === $id);

        if ($index === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($targetIndex < 0 || $targetIndex >= $links->count()) {
            return;
        }

        $current = $links[$index];
        $other = $links[$targetIndex];
        $currentSort = $current->sort_order;

        $current->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $currentSort]);

        $this->flashSaved();
    }

    public function resetLinkForm(): void
    {
        $this->authorizeStoreSettings();
        $this->editingId = null;
        $this->link_group = StoreFooterLink::GROUP_QUICK;
        $this->label_en = '';
        $this->label_ur = '';
        $this->url = '';
        $this->category_id = null;
        $this->store_page_id = null;
        $this->opens_in_new_tab = false;
    }

    public function render(): View
    {
        if (! $this->canManageStoreSettings()) {
            return view('livewire.store.footer-settings', [
                'quickLinks' => collect(),
                'registrationLinks' => collect(),
                'categories' => collect(),
                'pages' => collect(),
                'policyPages' => collect(),
                'legalPages' => collect(),
            ]);
        }

        return view('livewire.store.footer-settings', [
            'quickLinks' => StoreFooterLink::query()
                ->where('group', StoreFooterLink::GROUP_QUICK)
                ->with(['category:id,name_en,slug', 'storePage:id,title_en,slug'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'registrationLinks' => StoreFooterLink::query()
                ->where('group', StoreFooterLink::GROUP_REGISTRATION)
                ->with(['category:id,name_en,slug', 'storePage:id,title_en,slug'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'categories' => Category::query()->where('status', 'active')->orderBy('name_en')->limit(200)->get(['id', 'name_en', 'slug']),
            'pages' => StorePage::query()->where('is_active', true)->orderBy('title_en')->get(['id', 'title_en', 'slug']),
            'policyPages' => StorePage::query()->where('show_in_footer', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'title_en', 'slug']),
            'legalPages' => StorePage::query()->where('show_in_legal', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'title_en', 'slug']),
        ]);
    }
}
