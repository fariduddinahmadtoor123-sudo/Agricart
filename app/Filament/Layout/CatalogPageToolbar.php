<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Catalog\OverviewPage;

class CatalogPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return CatalogSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Catalog';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'catalog';
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
            'products' => [
                ...$config,
                'livewirePrefix' => 'products',
                'columnDataAttr' => 'data-product-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                    ['label' => __('agricart.form_panel.statuses.draft'), 'value' => 'status:draft'],
                ],
                'columns' => [
                    ['label' => __('agricart.products.list.col_image'), 'value' => 'image', 'visible' => true],
                    ['label' => __('agricart.products.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.products.list.col_label_code'), 'value' => 'label_code', 'visible' => true],
                    ['label' => __('agricart.products.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.products.list.col_name_ur'), 'value' => 'name_ur', 'visible' => false],
                    ['label' => __('agricart.products.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.products.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'labels' => [
                ...$config,
                'livewirePrefix' => 'labels',
                'interactive' => false,
            ],
            'categories' => [
                ...$config,
                'livewirePrefix' => 'categories',
                'columnDataAttr' => 'data-category-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                    ['label' => __('agricart.form_panel.statuses.draft'), 'value' => 'status:draft'],
                ],
                'columns' => [
                    ['label' => __('agricart.categories.list.col_image'), 'value' => 'image', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_name_ur'), 'value' => 'name_ur', 'visible' => false],
                    ['label' => __('agricart.categories.list.col_slug'), 'value' => 'slug', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_ai_status'), 'value' => 'ai_status', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'brands' => [
                ...$config,
                'livewirePrefix' => 'brands',
                'columnDataAttr' => 'data-brand-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.brands.list.col_image'), 'value' => 'image', 'visible' => true],
                    ['label' => __('agricart.brands.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.brands.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.brands.list.col_name_ur'), 'value' => 'name_ur', 'visible' => false],
                    ['label' => __('agricart.brands.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.brands.list.col_ai_status'), 'value' => 'ai_status', 'visible' => true],
                    ['label' => __('agricart.brands.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'units' => [
                ...$config,
                'livewirePrefix' => 'units',
                'columnDataAttr' => 'data-unit-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.units.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.units.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.units.list.col_abbreviation_en'), 'value' => 'abbreviation_en', 'visible' => true],
                    ['label' => __('agricart.units.list.col_name_ur'), 'value' => 'name_ur', 'visible' => true],
                    ['label' => __('agricart.units.list.col_abbreviation_ur'), 'value' => 'abbreviation_ur', 'visible' => false],
                    ['label' => __('agricart.units.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.units.list.col_ai_status'), 'value' => 'ai_status', 'visible' => true],
                    ['label' => __('agricart.units.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'packing-units' => [
                ...$config,
                'livewirePrefix' => 'packing-units',
                'columnDataAttr' => 'data-packing-unit-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.packing_units.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.packing_units.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.packing_units.list.col_name_ur'), 'value' => 'name_ur', 'visible' => true],
                    ['label' => __('agricart.packing_units.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.packing_units.list.col_ai_status'), 'value' => 'ai_status', 'visible' => true],
                    ['label' => __('agricart.packing_units.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'attributes' => [
                ...$config,
                'livewirePrefix' => 'attributes',
                'columnDataAttr' => 'data-attribute-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.attributes.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.attributes.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.attributes.list.col_name_ur'), 'value' => 'name_ur', 'visible' => true],
                    ['label' => __('agricart.attributes.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.attributes.list.col_ai_status'), 'value' => 'ai_status', 'visible' => true],
                    ['label' => __('agricart.attributes.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'controls' => [
                ...$config,
                'livewirePrefix' => 'controls',
                'columnDataAttr' => 'data-control-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.controls.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_value_en'), 'value' => 'value_en', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_name_ur'), 'value' => 'name_ur', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_value_ur'), 'value' => 'value_ur', 'visible' => false],
                    ['label' => __('agricart.controls.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_ai_status'), 'value' => 'ai_status', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            default => $config,
        };
    }
}
