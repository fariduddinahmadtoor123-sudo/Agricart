<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Marketplace\OverviewPage;

class MarketplacePageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return MarketplaceSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Channels Hub';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        return match ($config['resourceSlug']) {
            'price-control' => static::priceControlConfig($config),
            'quantity-control' => static::quantityControlConfig($config),
            'secret-coding' => static::secretCodingConfig($config),
            default => $config,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected static function priceControlConfig(array $config): array
    {
        return [
            ...$config,
            'livewirePrefix' => 'price-controls',
            'columnDataAttr' => 'data-price-control-column',
            'interactive' => true,
            'formKey' => 'price-controls',
            'addLabel' => static::userCan('create') ? __('price_controls.actions.add') : null,
            'filters' => static::statusFilters('price_controls'),
            'columns' => [
                ['label' => __('price_controls.list.col_name'), 'value' => 'name', 'visible' => true],
                ['label' => __('price_controls.list.col_markup'), 'value' => 'markup_percent', 'visible' => true],
                ['label' => __('price_controls.list.col_maps_to'), 'value' => 'product_field', 'visible' => true],
                ['label' => __('price_controls.list.col_example_short'), 'value' => 'example_price', 'visible' => true],
                ['label' => __('price_controls.list.col_status'), 'value' => 'status', 'visible' => true],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected static function quantityControlConfig(array $config): array
    {
        return [
            ...$config,
            'livewirePrefix' => 'quantity-controls',
            'columnDataAttr' => 'data-quantity-control-column',
            'interactive' => true,
            'formKey' => 'quantity-controls',
            'addLabel' => static::userCan('create') ? __('quantity_controls.actions.add') : null,
            'filters' => static::statusFilters('quantity_controls'),
            'columns' => [
                ['label' => __('quantity_controls.list.col_name'), 'value' => 'name', 'visible' => true],
                ['label' => __('quantity_controls.list.col_min_quantity'), 'value' => 'min_quantity', 'visible' => true],
                ['label' => __('quantity_controls.list.col_price_type'), 'value' => 'price_field', 'visible' => true],
                ['label' => __('quantity_controls.list.col_status'), 'value' => 'status', 'visible' => true],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected static function secretCodingConfig(array $config): array
    {
        return [
            ...$config,
            'livewirePrefix' => 'secret-codings',
            'columnDataAttr' => 'data-secret-coding-column',
            'interactive' => true,
            'formKey' => 'secret-coding',
            'addLabel' => static::userCan('create') ? __('secret_coding.actions.add') : null,
            'filters' => static::statusFilters('secret_coding'),
            'columns' => [
                ['label' => __('secret_coding.list.col_number'), 'value' => 'profile_number', 'visible' => true],
                ['label' => __('secret_coding.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                ['label' => __('secret_coding.list.col_name_ur'), 'value' => 'name_ur', 'visible' => true],
                ['label' => __('secret_coding.list.col_mapping'), 'value' => 'digit_mapping', 'visible' => true],
                ['label' => __('secret_coding.list.col_status'), 'value' => 'status', 'visible' => true],
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function statusFilters(string $langFile): array
    {
        return [
            ['label' => __($langFile . '.filters.active'), 'value' => 'status:active'],
            ['label' => __($langFile . '.filters.inactive'), 'value' => 'status:inactive'],
        ];
    }

    protected static function defaultResourceSlug(): string
    {
        return 'marketplace';
    }
}
