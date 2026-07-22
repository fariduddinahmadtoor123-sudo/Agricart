<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Contacts\OverviewPage;

class ContactsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ContactsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Contacts';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'contacts';
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
                'livewirePrefix' => 'contacts-overview',
                'interactive' => false,
            ],
            'suppliers' => [
                ...$config,
                'livewirePrefix' => 'suppliers',
                'columnDataAttr' => 'data-supplier-column',
                'interactive' => true,
                'formKey' => 'suppliers',
                'addLabel' => static::userCan('create') ? __('suppliers.actions.add') : null,
                'filters' => static::supplierFilters(),
                'toolbarActions' => array_values(array_filter([
                    static::userCan('view') ? [
                        'label' => __('suppliers.actions.export_archive'),
                        'value' => 'export_archive',
                        'color' => 'gray',
                        'outlined' => true,
                    ] : null,
                    static::userCan('create') ? [
                        'label' => __('suppliers.actions.import_archive'),
                        'value' => 'import_archive',
                        'color' => 'gray',
                        'outlined' => true,
                    ] : null,
                ])),
                'columns' => [
                    ['label' => __('suppliers.list.col_code'), 'value' => 'supplier_code', 'visible' => true],
                    ['label' => __('suppliers.list.col_business'), 'value' => 'business_name', 'visible' => true],
                    ['label' => __('suppliers.list.col_contact'), 'value' => 'contact_name', 'visible' => true],
                    ['label' => __('suppliers.list.col_city'), 'value' => 'city', 'visible' => true],
                    ['label' => __('suppliers.list.col_type'), 'value' => 'supplier_type', 'visible' => true],
                    ['label' => __('suppliers.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('suppliers.list.col_categories'), 'value' => 'categories', 'visible' => false],
                    ['label' => __('suppliers.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'customers' => [
                ...$config,
                'livewirePrefix' => 'customers',
                'columnDataAttr' => 'data-customer-column',
                'interactive' => true,
                'formKey' => 'customers',
                'addLabel' => static::userCan('create') ? __('customers.actions.add') : null,
                'filters' => static::customerFilters(),
                'toolbarActions' => array_values(array_filter([
                    static::userCan('view') ? [
                        'label' => __('customers.actions.export_all'),
                        'value' => 'export_all',
                        'color' => 'gray',
                        'outlined' => true,
                    ] : null,
                    static::userCan('view') ? [
                        'label' => __('customers.actions.export_online'),
                        'value' => 'export_online',
                        'color' => 'gray',
                        'outlined' => true,
                    ] : null,
                    static::userCan('view') ? [
                        'label' => __('customers.actions.export_offline'),
                        'value' => 'export_offline',
                        'color' => 'gray',
                        'outlined' => true,
                    ] : null,
                    static::userCan('create') ? [
                        'label' => __('customers.actions.import_archive'),
                        'value' => 'import_archive',
                        'color' => 'gray',
                        'outlined' => true,
                    ] : null,
                ])),
                'columns' => [
                    ['label' => __('customers.list.col_number'), 'value' => 'customer_number', 'visible' => true],
                    ['label' => __('customers.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('customers.list.col_phone'), 'value' => 'phone', 'visible' => true],
                    ['label' => __('customers.list.col_channel'), 'value' => 'channel', 'visible' => true],
                    ['label' => __('customers.list.col_city'), 'value' => 'city', 'visible' => true],
                    ['label' => __('customers.list.col_balance'), 'value' => 'balance', 'visible' => true],
                    ['label' => __('customers.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            default => $config,
        };
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function supplierFilters(): array
    {
        $filters = [
            ['label' => __('suppliers.filters.active'), 'value' => 'status:active'],
            ['label' => __('suppliers.filters.inactive'), 'value' => 'status:inactive'],
        ];

        foreach (config('pakistani_cities', []) as $key => $city) {
            if (! is_array($city)) {
                continue;
            }

            $filters[] = [
                'label' => (string) ($city['en'] ?? $key),
                'value' => 'city:' . $key,
            ];
        }

        return $filters;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function customerFilters(): array
    {
        return [
            ['label' => __('customers.filters.online'), 'value' => 'channel:online'],
            ['label' => __('customers.filters.offline'), 'value' => 'channel:offline'],
            ['label' => __('customers.filters.active'), 'value' => 'status:active'],
            ['label' => __('customers.filters.inactive'), 'value' => 'status:inactive'],
        ];
    }
}
