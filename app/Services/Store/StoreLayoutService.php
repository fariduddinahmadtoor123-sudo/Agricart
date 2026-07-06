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
            ->get(['id', 'slug', 'title_en', 'title_ur']);

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
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title_en', 'title_ur']);

        $footerRegistrationPages = StorePage::query()
            ->where('is_active', true)
            ->where('show_in_registration', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title_en', 'title_ur']);

        $footerPolicyPages = StorePage::query()
            ->where('is_active', true)
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title_en', 'title_ur']);

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
            'logo_url' => $logoUrl,
            'footer_logo_url' => $footerLogoUrl,
            'footer_quick_links' => $footerQuickLinks,
            'footer_quick_pages' => $footerQuickPages,
            'footer_registration_links' => $footerRegistrationLinks,
            'footer_registration_pages' => $footerRegistrationPages,
            'footer_policy_pages' => $footerPolicyPages,
        ];
    }
}
