<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Sales\OverviewPage;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderStaffAssignment;
use App\Models\Sale;
use App\Models\User;
use App\Services\Sales\SaleQuotationService;
use App\Services\Sales\SaleService;

class SalesPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return SalesSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Sales';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'sales';
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
                'livewirePrefix' => 'sales-overview',
                'interactive' => false,
            ],
            'pos-sales' => [
                ...$config,
                'livewirePrefix' => 'pos-sales',
                'columnDataAttr' => 'data-sale-column',
                'interactive' => true,
                'formKey' => 'sales',
                'addLabel' => static::userCan('create') ? __('pos.actions.add') : null,
                'compactAddButton' => true,
                'filterGroups' => static::saleFilterGroups(),
                'filters' => [],
                'columns' => [
                    ['label' => __('pos.list.col_number'), 'value' => 'sale_number', 'visible' => true],
                    ['label' => __('pos.list.col_channel'), 'value' => 'channel', 'visible' => true],
                    ['label' => __('pos.list.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('pos.list.col_cashier'), 'value' => 'cashier', 'visible' => true],
                    ['label' => __('pos.list.col_total'), 'value' => 'total_amount', 'visible' => true],
                    ['label' => __('pos.list.col_invoice_status'), 'value' => 'invoice_status', 'visible' => true],
                    ['label' => __('pos.list.col_payment_status'), 'value' => 'payment_status', 'visible' => true],
                    ['label' => __('pos.list.col_payment_method'), 'value' => 'payment_method', 'visible' => true],
                    ['label' => __('pos.list.col_sale_date'), 'value' => 'sale_date', 'visible' => true],
                    ['label' => __('pos.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'sales-quotations' => [
                ...$config,
                'livewirePrefix' => 'sale-quotations',
                'columnDataAttr' => 'data-sale-quotation-column',
                'interactive' => true,
                'formKey' => 'sale-quotations',
                'addLabel' => static::userCan('create') ? __('sale_quotations.actions.add') : null,
                'compactAddButton' => true,
                'filters' => static::saleQuotationFilters(),
                'columns' => [
                    ['label' => __('sale_quotations.list.col_number'), 'value' => 'quotation_number', 'visible' => true],
                    ['label' => __('sale_quotations.list.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('sale_quotations.list.col_prepared_by'), 'value' => 'prepared_by', 'visible' => true],
                    ['label' => __('sale_quotations.list.col_amount'), 'value' => 'quotation_amount', 'visible' => true],
                    ['label' => __('sale_quotations.list.col_locale'), 'value' => 'locale', 'visible' => true],
                    ['label' => __('sale_quotations.list.col_quotation_date'), 'value' => 'quotation_date', 'visible' => true],
                    ['label' => __('sale_quotations.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'sales-returns' => [
                ...$config,
                'livewirePrefix' => 'sale-returns',
                'columnDataAttr' => 'data-sale-return-column',
                'interactive' => true,
                'formKey' => 'sale-returns',
                'addLabel' => static::userCan('create') ? __('sale_returns.actions.add') : null,
                'compactAddButton' => true,
                'filterGroups' => static::saleReturnFilterGroups(),
                'filters' => [],
                'columns' => [
                    ['label' => __('sale_returns.list.col_number'), 'value' => 'return_number', 'visible' => true],
                    ['label' => __('sale_returns.list.col_sale_number'), 'value' => 'sale_number', 'visible' => true],
                    ['label' => __('sale_returns.list.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('sale_returns.list.col_refund_total'), 'value' => 'refund_total', 'visible' => true],
                    ['label' => __('sale_returns.list.col_payment_status'), 'value' => 'payment_status', 'visible' => true],
                    ['label' => __('sale_returns.list.col_payment_method'), 'value' => 'payment_method', 'visible' => true],
                    ['label' => __('sale_returns.list.col_return_date'), 'value' => 'return_date', 'visible' => true],
                    ['label' => __('sale_returns.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'online-quotations' => [
                ...$config,
                'livewirePrefix' => 'online-quotations',
                'columnDataAttr' => 'data-online-quotation-column',
                'interactive' => true,
                'formKey' => 'online-orders',
                'addLabel' => null,
                'compactAddButton' => false,
                'filterGroups' => static::onlineQuotationFilterGroups(),
                'filters' => [],
                'columns' => [
                    ['label' => __('online_sale_orders.list.col_number'), 'value' => 'order_number', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_total'), 'value' => 'total_amount', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_order_status'), 'value' => 'order_status', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_receipt_status'), 'value' => 'receipt_status', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_payment_status'), 'value' => 'payment_status', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_customer_payment'), 'value' => 'customer_payment_method', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_order_date'), 'value' => 'order_date', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'online-orders' => [
                ...$config,
                'livewirePrefix' => 'online-orders',
                'columnDataAttr' => 'data-online-order-column',
                'interactive' => true,
                'formKey' => 'online-orders',
                'addLabel' => null,
                'compactAddButton' => false,
                'filterGroups' => [],
                'filters' => [],
                'columns' => [
                    ['label' => __('online_sale_orders.list.col_number'), 'value' => 'order_number', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_sale_number'), 'value' => 'sale_number', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_total'), 'value' => 'total_amount', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_payment_status'), 'value' => 'payment_status', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_order_date'), 'value' => 'order_date', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            'order-dispatch' => [
                ...$config,
                'livewirePrefix' => 'order-dispatch',
                'columnDataAttr' => 'data-order-dispatch-column',
                'interactive' => true,
                'formKey' => 'online-orders',
                'addLabel' => null,
                'compactAddButton' => false,
                'hideFilters' => true,
                'filterGroups' => [],
                'filters' => [],
                'columns' => [
                    ['label' => __('online_sale_orders.list.col_number'), 'value' => 'order_number', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_invoice_number'), 'value' => 'invoice_number', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_total'), 'value' => 'total_amount', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_order_date'), 'value' => 'order_date', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_created'), 'value' => 'created_at', 'visible' => false],
                ],
            ],
            'forwarded-orders' => [
                ...$config,
                'livewirePrefix' => 'forwarded-orders',
                'interactive' => true,
                'formKey' => 'online-orders',
                'addLabel' => null,
                'compactAddButton' => false,
                'hideColumns' => true,
                'searchPlaceholder' => __('forwarded_orders.list.search'),
                'filterGroups' => static::forwardedOrdersFilterGroups(),
                'filters' => [],
                'columns' => [],
            ],
            'inbox' => [
                ...$config,
                'livewirePrefix' => 'staff-inbox',
                'interactive' => true,
                'formKey' => 'online-orders',
                'addLabel' => null,
                'compactAddButton' => false,
                'hideColumns' => true,
                'searchPlaceholder' => __('staff_inbox.list.search'),
                'filterGroups' => [
                    [
                        'label' => __('staff_inbox.filters.all'),
                        'filters' => [
                            ['label' => __('staff_inbox.filters.all'), 'value' => 'status:all'],
                            ['label' => __('staff_inbox.filters.unread'), 'value' => 'status:unread'],
                        ],
                    ],
                ],
                'filters' => [],
                'columns' => [],
            ],
            default => $config,
        };
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function forwardedOrdersFilterGroups(): array
    {
        $fromIds = OnlineSaleOrderStaffAssignment::query()
            ->whereNotNull('sent_at')
            ->whereNotNull('assigned_by_id')
            ->distinct()
            ->pluck('assigned_by_id');

        $toIds = OnlineSaleOrderStaffAssignment::query()
            ->whereNotNull('sent_at')
            ->distinct()
            ->pluck('user_id');

        $fromFilters = User::query()
            ->whereIn('id', $fromIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'label' => (string) $user->name,
                'value' => 'from:'.$user->id,
            ])
            ->values()
            ->all();

        $toFilters = User::query()
            ->whereIn('id', $toIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'label' => (string) $user->name,
                'value' => 'to:'.$user->id,
            ])
            ->values()
            ->all();

        $groups = [];

        if ($fromFilters !== []) {
            $groups[] = [
                'label' => __('forwarded_orders.filters.from'),
                'filters' => $fromFilters,
            ];
        }

        if ($toFilters !== []) {
            $groups[] = [
                'label' => __('forwarded_orders.filters.to'),
                'filters' => $toFilters,
            ];
        }

        return $groups;
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function saleFilterGroups(): array
    {
        $invoiceFilters = [];
        foreach (Sale::STATUSES as $status) {
            $invoiceFilters[] = [
                'label' => __('pos.invoice_statuses.' . $status),
                'value' => 'invoice:' . $status,
            ];
        }

        $paymentFilters = [];
        foreach (SaleService::PAYMENT_STATUSES as $status) {
            $paymentFilters[] = [
                'label' => __('pos.payment_statuses.' . $status),
                'value' => 'payment:' . $status,
            ];
        }

        $methodFilters = [];
        foreach (SaleService::PAYMENT_METHODS as $method) {
            $methodFilters[] = [
                'label' => __('pos.payment_methods.' . $method),
                'value' => 'method:' . $method,
            ];
        }

        return [
            [
                'label' => __('pos.filters.channel'),
                'filters' => [
                    [
                        'label' => __('pos.channels.online'),
                        'value' => 'channel:online',
                    ],
                    [
                        'label' => __('pos.channels.offline'),
                        'value' => 'channel:offline',
                    ],
                ],
            ],
            [
                'label' => __('pos.filters.invoice'),
                'filters' => $invoiceFilters,
            ],
            [
                'label' => __('pos.filters.payment'),
                'filters' => $paymentFilters,
            ],
            [
                'label' => __('pos.filters.method'),
                'filters' => $methodFilters,
            ],
        ];
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function saleReturnFilterGroups(): array
    {
        $paymentFilters = [];
        foreach (SaleService::PAYMENT_STATUSES as $status) {
            $paymentFilters[] = [
                'label' => __('pos.payment_statuses.' . $status),
                'value' => 'payment:' . $status,
            ];
        }

        $methodFilters = [];
        foreach (SaleService::PAYMENT_METHODS as $method) {
            $methodFilters[] = [
                'label' => __('pos.payment_methods.' . $method),
                'value' => 'method:' . $method,
            ];
        }

        return [
            [
                'label' => __('pos.filters.payment'),
                'filters' => $paymentFilters,
            ],
            [
                'label' => __('pos.filters.method'),
                'filters' => $methodFilters,
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function saleQuotationFilters(): array
    {
        $filters = [];

        foreach (SaleQuotationService::LOCALES as $locale) {
            $filters[] = [
                'label' => __('sale_quotations.locales.' . $locale),
                'value' => 'locale:' . $locale,
            ];
        }

        return $filters;
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function onlineQuotationFilterGroups(): array
    {
        $orderFilters = [];
        foreach (OnlineSaleOrder::STATUSES as $status) {
            if ($status === OnlineSaleOrder::STATUS_CONVERTED) {
                continue;
            }

            $orderFilters[] = [
                'label' => __('online_sale_orders.order_statuses.' . $status),
                'value' => 'order:' . $status,
            ];
        }

        $paymentFilters = [];
        foreach (SaleService::PAYMENT_STATUSES as $status) {
            $paymentFilters[] = [
                'label' => __('pos.payment_statuses.' . $status),
                'value' => 'payment:' . $status,
            ];
        }

        return [
            [
                'label' => __('online_sale_orders.filters.order'),
                'filters' => $orderFilters,
            ],
            [
                'label' => __('pos.filters.payment'),
                'filters' => $paymentFilters,
            ],
        ];
    }
}
