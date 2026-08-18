<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Purchasing\OverviewPage;
use App\Services\Purchasing\PurchasePaymentSummaryService;
use App\Services\Purchasing\PurchasePlanningService;
use App\Services\Purchasing\PurchaseQuotationService;
use App\Services\Purchasing\PurchaseService;

class PurchasingPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return PurchasingSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Purchasing';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'purchasing';
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
                'livewirePrefix' => 'purchasing-overview',
                'interactive' => false,
                'hideFilters' => true,
                'hideColumns' => true,
            ],
            'purchase-planning' => [
                ...$config,
                'livewirePrefix' => 'purchase-planning',
                'columnDataAttr' => 'data-planning-column',
                'interactive' => true,
                'formKey' => 'purchase-planning',
                'addLabel' => static::userCan('create') ? __('purchase_planning.actions.add') : null,
                'compactAddButton' => true,
                'filterGroups' => static::planningFilterGroups(),
                'filters' => [],
                'columns' => [
                    ['label' => __('purchase_planning.list.col_number'), 'value' => 'planning_number', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_items'), 'value' => 'item_count', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_paper'), 'value' => 'paper_size', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_locale'), 'value' => 'locale', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_created_by'), 'value' => 'created_by', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'purchase-quotations' => [
                ...$config,
                'livewirePrefix' => 'purchase-quotations',
                'columnDataAttr' => 'data-quotation-column',
                'interactive' => true,
                'formKey' => 'purchase-quotations',
                'addLabel' => static::userCan('create') ? __('purchase_quotations.actions.add') : null,
                'compactAddButton' => true,
                'filters' => static::quotationFilters(),
                'columns' => [
                    ['label' => __('purchase_quotations.list.col_number'), 'value' => 'quotation_number', 'visible' => true],
                    ['label' => __('purchase_quotations.list.col_supplier'), 'value' => 'supplier', 'visible' => true],
                    ['label' => __('purchase_quotations.list.col_items'), 'value' => 'item_count', 'visible' => true],
                    ['label' => __('purchase_quotations.list.col_locale'), 'value' => 'locale', 'visible' => true],
                    ['label' => __('purchase_quotations.list.col_created_by'), 'value' => 'created_by', 'visible' => true],
                    ['label' => __('purchase_quotations.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'purchases' => [
                ...$config,
                'livewirePrefix' => 'purchases',
                'columnDataAttr' => 'data-purchase-column',
                'interactive' => true,
                'formKey' => 'purchases',
                'addLabel' => static::userCan('create') ? __('purchases.actions.add') : null,
                'compactAddButton' => true,
                'filterGroups' => static::purchaseFilterGroups(),
                'filters' => [],
                'columns' => [
                    ['label' => __('purchases.list.col_number'), 'value' => 'purchase_number', 'visible' => true],
                    ['label' => __('purchases.list.col_supplier'), 'value' => 'supplier', 'visible' => true],
                    ['label' => __('purchases.list.col_purchaser'), 'value' => 'purchaser', 'visible' => true],
                    ['label' => __('purchases.list.col_total'), 'value' => 'total_amount', 'visible' => true],
                    ['label' => __('purchases.list.col_delivery'), 'value' => 'delivery_status', 'visible' => true],
                    ['label' => __('purchases.list.col_payment'), 'value' => 'payment_status', 'visible' => true],
                    ['label' => __('purchases.list.col_dispute'), 'value' => 'dispute_status', 'visible' => true],
                    ['label' => __('purchases.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'purchase-payment-summary' => [
                ...$config,
                'livewirePrefix' => 'purchase-payment-summaries',
                'columnDataAttr' => 'data-payment-summary-column',
                'interactive' => true,
                'formKey' => 'purchase-payment-summary',
                'addLabel' => static::userCan('create') ? __('purchase_payment_summary.actions.add') : null,
                'compactAddButton' => true,
                'filters' => static::paymentSummaryFilters(),
                'columns' => [
                    ['label' => __('purchase_payment_summary.list.col_number'), 'value' => 'summary_number', 'visible' => true],
                    ['label' => __('purchase_payment_summary.list.col_date'), 'value' => 'summary_date', 'visible' => true],
                    ['label' => __('purchase_payment_summary.list.col_paid_lines'), 'value' => 'paid_lines', 'visible' => true],
                    ['label' => __('purchase_payment_summary.list.col_total'), 'value' => 'total_payments', 'visible' => true],
                    ['label' => __('purchase_payment_summary.list.col_locale'), 'value' => 'locale', 'visible' => true],
                    ['label' => __('purchase_payment_summary.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            're-order-center' => [
                ...$config,
                'livewirePrefix' => 'reorder',
                'interactive' => true,
                'hideColumns' => true,
                'formKey' => null,
                'addLabel' => null,
                'searchPlaceholder' => __('reorder.toolbar.search'),
                'segments' => [
                    ['label' => __('reorder.tabs.reorder'), 'value' => 'reorder'],
                    ['label' => __('reorder.tabs.queue'), 'value' => 'queue', 'badge' => true],
                ],
                'filterGroups' => static::reorderFilterGroups(),
                'filters' => [],
                'columns' => [],
                'toolbarActions' => static::reorderToolbarActions(),
            ],
            'price-tags' => [
                ...$config,
                'livewirePrefix' => 'price-tags',
                'livewireComponent' => 'price-tags.list-price-tags',
                'interactive' => true,
                'hideColumns' => true,
                'formKey' => null,
                'addLabel' => null,
                'searchPlaceholder' => __('price_tags.toolbar.search'),
                'filterGroups' => static::priceTagFilterGroups(),
                'filters' => [],
                'columns' => [],
                'toolbarActions' => static::userCan('print') ? [
                    [
                        'label' => __('price_tags.actions.print'),
                        'value' => 'print',
                        'icon' => \Filament\Support\Icons\Heroicon::OutlinedPrinter,
                        'color' => 'primary',
                        'outlined' => false,
                    ],
                ] : [],
            ],
            'rate-list' => [
                ...$config,
                'livewirePrefix' => 'rate-list',
                'livewireComponent' => 'rate-lists.list-rate-lists',
                'interactive' => true,
                'hideColumns' => true,
                'formKey' => null,
                'addLabel' => null,
                'searchPlaceholder' => __('rate_lists.toolbar.search'),
                'filterGroups' => static::rateListFilterGroups(),
                'filters' => [],
                'columns' => [],
                'toolbarActions' => static::userCan('print') ? [
                    [
                        'label' => __('rate_lists.actions.print'),
                        'value' => 'print',
                        'icon' => \Filament\Support\Icons\Heroicon::OutlinedPrinter,
                        'color' => 'primary',
                        'outlined' => false,
                    ],
                ] : [],
            ],
            'price-update' => [
                ...$config,
                'livewirePrefix' => 'price-updates',
                'livewireComponent' => 'price-updates.list-price-updates',
                'interactive' => true,
                'formKey' => 'price-update',
                'addLabel' => static::userCan('edit') ? __('price_updates.actions.add') : null,
                'compactAddButton' => true,
                'searchPlaceholder' => __('price_updates.toolbar.search'),
                'hideFilters' => true,
                'filters' => [],
                'columns' => [
                    ['label' => __('price_updates.list.col_thumb'), 'value' => 'thumb', 'visible' => true],
                    ['label' => __('price_updates.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('price_updates.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('price_updates.list.col_buy'), 'value' => 'last_buy_price', 'visible' => true],
                    ['label' => __('price_updates.list.col_retail'), 'value' => 'retail_price', 'visible' => true],
                    ['label' => __('price_updates.list.col_wholesale'), 'value' => 'wholesale_price', 'visible' => true],
                    ['label' => __('price_updates.list.col_bulk'), 'value' => 'super_wholesale_price', 'visible' => true],
                    ['label' => __('price_updates.list.col_distributor'), 'value' => 'distributor_price', 'visible' => true],
                    ['label' => __('price_updates.list.col_updated'), 'value' => 'updated_at', 'visible' => true],
                ],
            ],
            default => $config,
        };
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function purchaseFilterGroups(): array
    {
        $deliveryFilters = [];
        foreach (PurchaseService::DELIVERY_STATUSES as $status) {
            $deliveryFilters[] = [
                'label' => __('purchases.statuses.delivery.' . $status),
                'value' => 'delivery:' . $status,
            ];
        }

        $paymentFilters = [];
        foreach (PurchaseService::PAYMENT_STATUSES as $status) {
            $paymentFilters[] = [
                'label' => __('purchases.statuses.payment.' . $status),
                'value' => 'payment:' . $status,
            ];
        }

        $disputeFilters = [];
        foreach (PurchaseService::DISPUTE_STATUSES as $status) {
            $disputeFilters[] = [
                'label' => __('purchases.statuses.dispute.' . $status),
                'value' => 'dispute:' . $status,
            ];
        }

        return [
            [
                'label' => __('purchases.filters.delivery'),
                'filters' => $deliveryFilters,
            ],
            [
                'label' => __('purchases.filters.payment'),
                'filters' => $paymentFilters,
            ],
            [
                'label' => __('purchases.filters.dispute'),
                'filters' => $disputeFilters,
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function paymentSummaryFilters(): array
    {
        $filters = [];

        foreach (PurchasePaymentSummaryService::LOCALES as $locale) {
            $filters[] = [
                'label' => __('purchase_payment_summary.locales.' . $locale),
                'value' => 'locale:' . $locale,
            ];
        }

        return $filters;
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function planningFilterGroups(): array
    {
        $paperFilters = [];
        foreach (PurchasePlanningService::PAPER_SIZES as $size) {
            $paperFilters[] = [
                'label' => __('purchase_planning.paper_sizes.' . $size),
                'value' => 'paper_size:' . $size,
            ];
        }

        $localeFilters = [];
        foreach (PurchasePlanningService::LOCALES as $locale) {
            $localeFilters[] = [
                'label' => __('purchase_planning.locales.' . $locale),
                'value' => 'locale:' . $locale,
            ];
        }

        return [
            [
                'label' => __('purchase_planning.filters.paper_size'),
                'filters' => $paperFilters,
            ],
            [
                'label' => __('purchase_planning.filters.locale'),
                'filters' => $localeFilters,
            ],
        ];
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function priceTagFilterGroups(): array
    {
        return [
            [
                'label' => __('price_tags.filters.readiness'),
                'filters' => [
                    ['label' => __('price_tags.filters.ready_to_label'), 'value' => 'readiness:ready'],
                    ['label' => __('price_tags.filters.missing_sale_price'), 'value' => 'readiness:missing_sale_price'],
                    ['label' => __('price_tags.filters.missing_barcode'), 'value' => 'readiness:missing_barcode'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function rateListFilterGroups(): array
    {
        return [
            [
                'label' => __('rate_lists.filters.language'),
                'filters' => [
                    ['label' => __('rate_lists.locales.en'), 'value' => 'locale:en'],
                    ['label' => __('rate_lists.locales.ur'), 'value' => 'locale:ur'],
                    ['label' => __('rate_lists.locales.both'), 'value' => 'locale:both'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function quotationFilters(): array
    {
        $filters = [];

        foreach (PurchaseQuotationService::LOCALES as $locale) {
            $filters[] = [
                'label' => __('purchase_quotations.locales.' . $locale),
                'value' => 'locale:' . $locale,
            ];
        }

        return $filters;
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function reorderFilterGroups(): array
    {
        $reorderFilters = [];

        foreach (['available', 'in_queue', 'repeated', 'all'] as $filter) {
            $reorderFilters[] = [
                'label' => __('reorder.filters.' . $filter),
                'value' => 'reorder:' . $filter,
            ];
        }

        return [
            [
                'label' => __('reorder.tabs.reorder'),
                'filters' => $reorderFilters,
            ],
            [
                'label' => __('reorder.tabs.queue'),
                'filters' => [
                    ['label' => __('reorder.filters.pending'), 'value' => 'queue:pending'],
                    ['label' => __('reorder.filters.stale'), 'value' => 'queue:stale'],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function reorderToolbarActions(): array
    {
        $actions = [];

        if (static::userCan('print')) {
            $actions[] = [
                'label' => __('reorder.actions.print'),
                'value' => 'print',
                'icon' => \Filament\Support\Icons\Heroicon::OutlinedPrinter,
                'outlined' => true,
            ];
        }

        if (static::userCan('create')) {
            $actions = [
                [
                    'label' => __('reorder.actions.select_all'),
                    'value' => 'select_all',
                    'tab' => 'reorder',
                    'outlined' => true,
                ],
                [
                    'label' => __('reorder.actions.send_to_queue'),
                    'value' => 'send_to_queue',
                    'tab' => 'reorder',
                    'color' => 'primary',
                    'outlined' => false,
                    'icon' => \Filament\Support\Icons\Heroicon::OutlinedPaperAirplane,
                ],
                ...$actions,
            ];
        }

        return $actions;
    }
}
