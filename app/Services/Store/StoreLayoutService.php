<?php

namespace App\Services\Store;

use App\Models\StoreFooterLink;
use App\Models\StoreMenuItem;
use App\Models\StorePage;
use App\Models\StoreSetting;
use App\Models\StoreTickerMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class StoreLayoutService
{
    public function clearCache(): void
    {
        Cache::forget('store.layout');
    }

    /**
     * @return array{
     *     settings: StoreSetting,
     *     ticker_messages: Collection<int, StoreTickerMessage>,
     *     menu_items: Collection<int, StoreMenuItem>,
     *     logo_url: ?string,
     *     footer_logo_url: ?string,
     *     footer_quick_links: Collection<int, StoreFooterLink>,
     *     footer_quick_pages: Collection<int, StorePage>,
     *     footer_registration_links: Collection<int, StoreFooterLink>,
     *     footer_registration_pages: Collection<int, StorePage>,
     *     footer_policy_pages: Collection<int, StorePage>
     * }
     */
    public function layoutData(): array
    {
        // Do not cache Eloquent models — serialized models can become __PHP_Incomplete_Class
        // when the cache is read before autoloading, which breaks the storefront.
        return $this->buildLayoutData();
    }

    /**
     * @return array{
     *     settings: StoreSetting,
     *     ticker_messages: Collection<int, StoreTickerMessage>,
     *     menu_items: Collection<int, StoreMenuItem>,
     *     logo_url: ?string,
     *     footer_logo_url: ?string,
     *     footer_quick_links: Collection<int, StoreFooterLink>,
     *     footer_quick_pages: Collection<int, StorePage>,
     *     footer_registration_links: Collection<int, StoreFooterLink>,
     *     footer_registration_pages: Collection<int, StorePage>,
     *     footer_policy_pages: Collection<int, StorePage>
     * }
     */
    private function buildLayoutData(): array
    {
        $settings = StoreSetting::current();

        $tickerMessages = StoreTickerMessage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $menuItems = StoreMenuItem::query()
            ->where('is_active', true)
            ->with(['category:id,slug', 'storePage:id,slug'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $autoMenuPages = StorePage::query()
            ->where('is_active', true)
            ->where('show_in_menu', true)
            ->whereNotIn('id', $menuItems->pluck('store_page_id')->filter()->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title_en', 'title_ur', 'sort_order']);

        $menuNavEntries = $this->mergeNavEntries($autoMenuPages, $menuItems);

        $footerQuickLinks = StoreFooterLink::query()
            ->where('group', StoreFooterLink::GROUP_QUICK)
            ->where('is_active', true)
            ->with(['category:id,slug', 'storePage:id,slug,title_en,title_ur'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $footerRegistrationLinks = StoreFooterLink::query()
            ->where('group', StoreFooterLink::GROUP_REGISTRATION)
            ->where('is_active', true)
            ->with(['category:id,slug', 'storePage:id,slug,title_en,title_ur'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $footerQuickPages = StorePage::query()
            ->where('is_active', true)
            ->where('show_in_quick_links', true)
            ->whereNotIn('id', $footerQuickLinks->pluck('store_page_id')->filter()->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title_en', 'title_ur', 'sort_order']);

        $footerRegistrationPages = StorePage::query()
            ->where('is_active', true)
            ->where('show_in_registration', true)
            ->whereNotIn('id', $footerRegistrationLinks->pluck('store_page_id')->filter()->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title_en', 'title_ur', 'sort_order']);

        $footerPolicyPages = StorePage::query()
            ->where('is_active', true)
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title_en', 'title_ur']);

        $footerQuickEntries = $this->mergeFooterEntries($footerQuickPages, $footerQuickLinks);
        $footerRegistrationEntries = $this->mergeFooterEntries($footerRegistrationPages, $footerRegistrationLinks);

        $logoUrl = filled($settings->logo_path)
            ? Storage::disk('public')->url($settings->logo_path)
            : null;

        $footerLogoPath = $settings->footerLogoPath();
        $footerLogoUrl = filled($footerLogoPath)
            ? Storage::disk('public')->url($footerLogoPath)
            : null;

        return [
            'settings' => $settings,
            'ticker_messages' => $tickerMessages,
            'menu_items' => $menuItems,
            'auto_menu_pages' => $autoMenuPages,
            'menu_nav_entries' => $menuNavEntries,
            'logo_url' => $logoUrl,
            'footer_logo_url' => $footerLogoUrl,
            'footer_quick_links' => $footerQuickLinks,
            'footer_quick_pages' => $footerQuickPages,
            'footer_quick_entries' => $footerQuickEntries,
            'footer_registration_links' => $footerRegistrationLinks,
            'footer_registration_pages' => $footerRegistrationPages,
            'footer_registration_entries' => $footerRegistrationEntries,
            'footer_policy_pages' => $footerPolicyPages,
        ];
    }

    /**
     * @param  Collection<int, StorePage>  $pages
     * @param  Collection<int, StoreMenuItem>  $items
     * @return Collection<int, array{type: string, sort_order: int, page?: StorePage, item?: StoreMenuItem}>
     */
    private function mergeNavEntries(Collection $pages, Collection $items): Collection
    {
        return collect()
            ->merge($pages->map(static fn (StorePage $page): array => [
                'type' => 'page',
                'sort_order' => (int) $page->sort_order,
                'page' => $page,
            ]))
            ->merge($items->map(static fn (StoreMenuItem $item): array => [
                'type' => 'item',
                'sort_order' => (int) $item->sort_order,
                'item' => $item,
            ]))
            ->sortBy([
                fn (array $entry): int => $entry['sort_order'],
                fn (array $entry): int => $entry['type'] === 'page'
                    ? (int) ($entry['page']->id ?? 0)
                    : (int) ($entry['item']->id ?? 0),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, StorePage>  $pages
     * @param  Collection<int, StoreFooterLink>  $links
     * @return Collection<int, array{type: string, sort_order: int, page?: StorePage, link?: StoreFooterLink}>
     */
    private function mergeFooterEntries(Collection $pages, Collection $links): Collection
    {
        return collect()
            ->merge($pages->map(static fn (StorePage $page): array => [
                'type' => 'page',
                'sort_order' => (int) $page->sort_order,
                'page' => $page,
            ]))
            ->merge($links->map(static fn (StoreFooterLink $link): array => [
                'type' => 'link',
                'sort_order' => (int) $link->sort_order,
                'link' => $link,
            ]))
            ->sortBy([
                fn (array $entry): int => $entry['sort_order'],
                fn (array $entry): int => $entry['type'] === 'page'
                    ? (int) ($entry['page']->id ?? 0)
                    : (int) ($entry['link']->id ?? 0),
            ])
            ->values();
    }
}
