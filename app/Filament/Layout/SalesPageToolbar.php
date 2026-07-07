<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Sales\OverviewPage;
use App\Models\Sale;
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
            default => $config,
        };
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
}
