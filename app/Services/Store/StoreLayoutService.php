<?php

namespace App\Services\Store;

use App\Models\StoreMenuItem;
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
     *     logo_url: ?string
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

        $autoMenuPages = \App\Models\StorePage::query()
            ->where('is_active', true)
            ->where('show_in_menu', true)
            ->whereNotIn('id', $menuItems->pluck('store_page_id')->filter()->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title_en', 'title_ur']);

        $logoUrl = filled($settings->logo_path)
            ? Storage::disk('public')->url($settings->logo_path)
            : null;

        return [
            'settings' => $settings,
            'ticker_messages' => $tickerMessages,
            'menu_items' => $menuItems,
            'auto_menu_pages' => $autoMenuPages,
            'logo_url' => $logoUrl,
        ];
    }
}
