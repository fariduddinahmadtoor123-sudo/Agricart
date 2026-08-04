<?php

namespace App\Services\SalesChannels;

use App\Models\SalesChannel;

class SalesChannelCatalog
{
    /**
     * Known channel types. Set `implemented` true when the integration is ready to register in the app.
     *
     * @return array<string, array{name_en: string, name_ur: string, implemented: bool, sort_order: int}>
     */
    public static function definitions(): array
    {
        return [
            SalesChannel::SLUG_ONLINE_STORE => [
                'name_en' => 'Online store (own website)',
                'name_ur' => 'آن لائن اسٹور (اپنی ویب سائٹ)',
                'implemented' => true,
                'sort_order' => 1,
            ],
            SalesChannel::SLUG_FACEBOOK => [
                'name_en' => 'Facebook & Instagram (Meta Catalog)',
                'name_ur' => 'فیس بک اور انسٹاگرام (میٹا کیٹلاگ)',
                'implemented' => true,
                'sort_order' => 2,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function implementedSlugs(): array
    {
        $slugs = [];

        foreach (static::definitions() as $slug => $definition) {
            if ($definition['implemented']) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * @return array<string, string> slug => localized label
     */
    public static function dropdownLabels(): array
    {
        $locale = app()->getLocale();
        $options = [];

        foreach (static::definitions() as $slug => $definition) {
            if (! $definition['implemented']) {
                continue;
            }

            $options[$slug] = ($locale === 'ur' && filled($definition['name_ur']))
                ? $definition['name_ur']
                : $definition['name_en'];
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultsForSlug(string $slug): array
    {
        $definition = static::definitions()[$slug] ?? null;

        if ($definition === null) {
            return [];
        }

        $defaults = [
            'slug' => $slug,
            'name_en' => $definition['name_en'],
            'name_ur' => $definition['name_ur'],
            'sort_order' => $definition['sort_order'],
            'is_enabled' => true,
            'offline_message_en' => __('sales_channels.defaults.offline_message'),
            'offline_message_ur' => __('sales_channels.defaults.offline_message_ur'),
            'banner_message_en' => __('sales_channels.defaults.banner_off_en'),
            'banner_message_ur' => __('sales_channels.defaults.banner_off_ur'),
        ];

        if ($slug === SalesChannel::SLUG_FACEBOOK) {
            $defaults['is_enabled'] = false;
            $defaults['offline_message_en'] = null;
            $defaults['offline_message_ur'] = null;
            $defaults['banner_message_en'] = null;
            $defaults['banner_message_ur'] = null;
            $defaults['settings'] = [
                'page_url' => '',
                'feed_token' => bin2hex(random_bytes(24)),
                'price_field' => 'wholesale_price',
            ];
        }

        return $defaults;
    }
}
