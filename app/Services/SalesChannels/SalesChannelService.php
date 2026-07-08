<?php

namespace App\Services\SalesChannels;

use App\Models\SalesChannel;
use Illuminate\Support\Facades\Cache;

class SalesChannelService
{
    private const CACHE_KEY = 'sales_channels.online_store.v2';

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function onlineStore(): SalesChannel
    {
        return SalesChannel::onlineStore();
    }

    public function isOnlineStoreInMaintenance(): bool
    {
        return (bool) ($this->onlineStoreFlags()['maintenance_mode'] ?? false);
    }

    public function isOnlineStoreEnabled(): bool
    {
        return (bool) ($this->onlineStoreFlags()['enabled'] ?? true);
    }

    public function onlineStoreShowsPrices(): bool
    {
        return (bool) ($this->onlineStoreFlags()['show_prices'] ?? true);
    }

    public function onlineStoreAllowsPurchasing(): bool
    {
        return (bool) ($this->onlineStoreFlags()['allow_purchase'] ?? true);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     show_prices: bool,
     *     allow_purchase: bool,
     *     offline_message_en: string,
     *     offline_message_ur: string,
     *     banner_message_en: ?string,
     *     banner_message_ur: ?string
     * }
     */
    public function onlineStoreFlags(): array
    {
        return Cache::remember(self::CACHE_KEY, app()->environment('local') ? 10 : 300, function (): array {
            $channel = SalesChannel::onlineStore();
            $enabled = (bool) $channel->is_enabled;
            $maintenance = (bool) $channel->maintenance_mode;

            return [
                'enabled' => $enabled,
                'maintenance_mode' => $maintenance,
                'show_prices' => $enabled && ! $maintenance,
                'allow_purchase' => $enabled && ! $maintenance,
                'offline_message_en' => filled($channel->offline_message_en)
                    ? (string) $channel->offline_message_en
                    : __('sales_channels.defaults.offline_message'),
                'offline_message_ur' => filled($channel->offline_message_ur)
                    ? (string) $channel->offline_message_ur
                    : __('sales_channels.defaults.offline_message_ur'),
                'banner_message_en' => $channel->banner_message_en,
                'banner_message_ur' => $channel->banner_message_ur,
                'maintenance_message_en' => $channel->maintenance_message_en,
                'maintenance_message_ur' => $channel->maintenance_message_ur,
            ];
        });
    }

    /**
     * @return array{
     *     enabled: bool,
     *     show_prices: bool,
     *     allow_purchase: bool,
     *     offline_message: string,
     *     banner_message: ?string
     * }
     */
    public function onlineStoreViewState(): array
    {
        $flags = $this->onlineStoreFlags();
        $locale = app()->getLocale();
        $offlineKey = $locale === 'ur' ? 'offline_message_ur' : 'offline_message_en';
        $bannerKey = $locale === 'ur' ? 'banner_message_ur' : 'banner_message_en';

        return [
            'enabled' => $flags['enabled'],
            'maintenance_mode' => (bool) ($flags['maintenance_mode'] ?? false),
            'show_prices' => $flags['show_prices'],
            'allow_purchase' => $flags['allow_purchase'],
            'offline_message' => $flags[$offlineKey],
            'banner_message' => $this->resolveBannerMessage($flags, $bannerKey, $flags['enabled']),
        ];
    }

    /**
     * @return array{site_name: string, message: string, title: string}
     */
    public function maintenancePageData(): array
    {
        $flags = $this->onlineStoreFlags();
        $locale = app()->getLocale();
        $messageKey = $locale === 'ur' ? 'maintenance_message_ur' : 'maintenance_message_en';
        $fallbackKey = $locale === 'ur' ? 'maintenance_message_en' : 'maintenance_message_ur';

        $message = filled($flags[$messageKey] ?? null)
            ? (string) $flags[$messageKey]
            : (filled($flags[$fallbackKey] ?? null)
                ? (string) $flags[$fallbackKey]
                : ($locale === 'ur'
                    ? __('sales_channels.defaults.maintenance_message_ur')
                    : __('sales_channels.defaults.maintenance_message')));

        $siteName = (string) config('app.name', 'Agricart');

        try {
            $settings = \App\Models\StoreSetting::current();
            $localized = $settings->localized('site_name');
            if (filled($localized)) {
                $siteName = (string) $localized;
            }
        } catch (\Throwable) {
            //
        }

        return [
            'site_name' => $siteName,
            'title' => __('sales_channels.maintenance.page_title'),
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $flags
     */
    protected function resolveBannerMessage(array $flags, string $bannerKey, bool $enabled): ?string
    {
        if ($enabled) {
            return filled($flags[$bannerKey] ?? null) ? (string) $flags[$bannerKey] : null;
        }

        if (filled($flags[$bannerKey] ?? null)) {
            return (string) $flags[$bannerKey];
        }

        return app()->getLocale() === 'ur'
            ? __('sales_channels.defaults.banner_off_ur')
            : __('sales_channels.defaults.banner_off_en');
    }

    public function assertOnlineStorePurchasingEnabled(): void
    {
        if (! $this->onlineStoreAllowsPurchasing()) {
            abort(403, __('agricart.storefront.channel.purchasing_disabled'));
        }
    }
}
