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
     * @return list<array{label: string, value: string, color: string, outlined: bool}>
     */
    protected static function portableArchiveToolbarActions(): array
    {
        return array_values(array_filter([
            static::userCan('view') ? [
                'label' => __('agricart.products.actions.export_archive'),
                'value' => 'export_archive',
                'color' => 'gray',
                'outlined' => true,
            ] : null,
            static::userCan('create') ? [
                'label' => __('agricart.products.actions.import_archive'),
                'value' => 'import_archive',
                'color' => 'gray',
                'outlined' => true,
            ] : null,
        ]));
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
            'overview' => [
                ...$config,
                'livewirePrefix' => 'catalog-overview',
                'interactive' => false,
            ],
            'product-reviews' => [
                ...$config,
                'livewirePrefix' => 'product-reviews',
                'formKey' => 'product-reviews',
                'columnDataAttr' => 'data-product-review-column',
                'interactive' => true,
                'addLabel' => static::userCan('create')
                    ? __('agricart.products.review_page.actions.add')
                    : null,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                    ['label' => __('agricart.form_panel.statuses.draft'), 'value' => 'status:draft'],
                ],
                'columns' => [
                    ['label' => __('agricart.products.list.col_image'), 'value' => 'image', 'visible' => true],
                    ['label' => __('agricart.products.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.products.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.products.list.col_name_ur'), 'value' => 'name_ur', 'visible' => false],
                    ['label' => __('agricart.products.fields.brand'), 'value' => 'brand', 'visible' => true],
                    ['label' => __('agricart.products.review_page.list.col_rating'), 'value' => 'agricart_rating', 'visible' => true],
                    ['label' => __('agricart.products.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.products.review_page.list.col_updated'), 'value' => 'updated_at', 'visible' => true],
                ],
            ],
            'products' => [
                ...$config,
                'livewirePrefix' => 'products',
                'livewireComponent' => 'products.list-products',
                'columnDataAttr' => 'data-product-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                    ['label' => __('agricart.form_panel.statuses.draft'), 'value' => 'status:draft'],
                ],
                'toolbarActions' => array_values(array_filter([
                    ...static::portableArchiveToolbarActions(),
                    static::userCan('view') ? [
                        'label' => __('agricart.products.actions.export_facebook_csv'),
                        'value' => 'export_facebook_csv',
                        'color' => 'gray',
                        'outlined' => true,
                    ] : null,
                ])),
                'columns' => [
                    ['label' => __('agricart.products.list.col_image'), 'value' => 'image', 'visible' => true],
                    ['label' => __('agricart.products.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.products.list.col_label_code'), 'value' => 'label_code', 'visible' => true],
                    ['label' => __('agricart.products.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.products.list.col_name_ur'), 'value' => 'name_ur', 'visible' => false],
                    ['label' => __('agricart.products.list.col_pack_unit'), 'value' => 'pack_unit', 'visible' => true],
                    ['label' => __('agricart.products.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.products.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'labels' => [
                ...$config,
                'livewirePrefix' => 'labels',
                'addLabel' => static::userCan('view')
                    ? __('agricart.labels.print.open_button')
                    : null,
                'formKey' => 'labels',
                'interactive' => false,
            ],
            'categories' => [
                ...$config,
                'livewirePrefix' => 'categories',
                'livewireComponent' => 'categories.list-categories',
                'columnDataAttr' => 'data-category-column',
                'interactive' => true,
                'toolbarActions' => static::portableArchiveToolbarActions(),
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
                'livewireComponent' => 'brands.list-brands',
                'columnDataAttr' => 'data-brand-column',
                'interactive' => true,
                'toolbarActions' => static::portableArchiveToolbarActions(),
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
            'packages' => [
                ...$config,
                'livewirePrefix' => 'packages',
                'livewireComponent' => 'packages.list-packages',
                'columnDataAttr' => 'data-package-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.packages.list.col_image'), 'value' => 'image', 'visible' => true],
                    ['label' => __('agricart.packages.list.col_package_number'), 'value' => 'package_number', 'visible' => true],
                    ['label' => __('agricart.packages.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.packages.list.col_name_ur'), 'value' => 'name_ur', 'visible' => false],
                    ['label' => __('agricart.packages.list.col_discount'), 'value' => 'discount_percent', 'visible' => true],
                    ['label' => __('agricart.packages.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.packages.list.col_sort_order'), 'value' => 'sort_order', 'visible' => false],
                    ['label' => __('agricart.packages.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'units' => [
                ...$config,
                'livewirePrefix' => 'units',
                'livewireComponent' => 'units.list-units',
                'columnDataAttr' => 'data-unit-column',
                'interactive' => true,
                'toolbarActions' => static::portableArchiveToolbarActions(),
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
                'livewireComponent' => 'packing-units.list-packing-units',
                'columnDataAttr' => 'data-packing-unit-column',
                'interactive' => true,
                'toolbarActions' => static::portableArchiveToolbarActions(),
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
                'livewireComponent' => 'attributes.list-attributes',
                'columnDataAttr' => 'data-attribute-column',
                'interactive' => true,
                'toolbarActions' => static::portableArchiveToolbarActions(),
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
                'livewireComponent' => 'controls.list-controls',
                'columnDataAttr' => 'data-control-column',
                'interactive' => true,
                'toolbarActions' => static::portableArchiveToolbarActions(),
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.controls.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_name_ur'), 'value' => 'name_ur', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_ai_status'), 'value' => 'ai_status', 'visible' => true],
                    ['label' => __('agricart.controls.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            default => $config,
        };
    }
}
