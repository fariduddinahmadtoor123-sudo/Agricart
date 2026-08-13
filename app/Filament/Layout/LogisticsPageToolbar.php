<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Logistics\OverviewPage;
use App\Models\OnlineSaleOrder;
use App\Models\User;

class LogisticsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return LogisticsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Logistics';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'logistics';
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
                'addLabel' => null,
                'formKey' => null,
                'hideSearch' => true,
                'hideFilters' => true,
                'hideColumns' => true,
                'interactive' => false,
            ],
            'delivery-methods' => [
                ...$config,
                'addLabel' => static::userCanManage() ? __('agricart.toolbar.add', ['item' => __('agricart.pages.logistics.delivery-methods')]) : null,
                'formKey' => 'delivery-methods',
                'livewirePrefix' => 'delivery-methods',
                'columnDataAttr' => 'data-delivery-method-column',
                'interactive' => true,
                'searchPlaceholder' => __('agricart.delivery_methods.list.search'),
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.delivery_methods.list.col_sort'), 'value' => 'sort_order', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_name_ur'), 'value' => 'name_ur', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_shipping_mode'), 'value' => 'shipping_charge_mode', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_cod'), 'value' => 'cod_allowed', 'visible' => true],
                ],
            ],
            'shipments' => [
                ...$config,
                'addLabel' => null,
                'formKey' => 'logistics-ship',
                'livewirePrefix' => 'pending-shipments',
                'columnDataAttr' => 'data-pending-shipment-column',
                'interactive' => true,
                'searchPlaceholder' => __('pending_shipments.list.search'),
                'filterGroups' => static::pendingShipmentFilterGroups(),
                'filters' => [],
                'columns' => [
                    ['label' => __('online_sale_orders.list.col_number'), 'value' => 'order_number', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_total'), 'value' => 'total_amount', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_queue_document'), 'value' => 'queue_document', 'visible' => true],
                    ['label' => __('order_dispatch.list.col_packing_progress'), 'value' => 'packing_progress', 'visible' => true],
                    ['label' => __('order_dispatch.list.col_fulfillment'), 'value' => 'fulfillment_status', 'visible' => true],
                    ['label' => __('pending_shipments.list.col_shipping'), 'value' => 'shipping', 'visible' => true],
                    ['label' => __('pending_shipments.list.col_queued'), 'value' => 'queued_at', 'visible' => true],
                ],
            ],
            'shipped' => [
                ...$config,
                'addLabel' => null,
                'formKey' => 'logistics-ship',
                'livewirePrefix' => 'shipped-orders',
                'columnDataAttr' => 'data-shipped-order-column',
                'interactive' => true,
                'searchPlaceholder' => __('shipped_orders.list.search'),
                'hideFilters' => true,
                'filters' => [],
                'columns' => [
                    ['label' => __('online_sale_orders.list.col_number'), 'value' => 'order_number', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('online_sale_orders.list.col_total'), 'value' => 'total_amount', 'visible' => true],
                    ['label' => __('order_dispatch.list.col_fulfillment'), 'value' => 'fulfillment_status', 'visible' => true],
                    ['label' => __('pending_shipments.list.col_shipping'), 'value' => 'shipping', 'visible' => true],
                    ['label' => __('shipped_orders.list.col_shipped'), 'value' => 'shipped_at', 'visible' => true],
                ],
            ],
            default => $config,
        };
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function pendingShipmentFilterGroups(): array
    {
        $fulfillmentFilters = [];

        foreach (OnlineSaleOrder::FULFILLMENT_STATUSES as $status) {
            if ($status === OnlineSaleOrder::FULFILLMENT_DELIVERED) {
                continue;
            }

            $fulfillmentFilters[] = [
                'label' => __('order_dispatch.fulfillment_statuses.' . $status),
                'value' => 'fulfillment:' . $status,
            ];
        }

        return [
            [
                'label' => __('order_dispatch.filters.fulfillment'),
                'filters' => $fulfillmentFilters,
            ],
        ];
    }

    protected static function userCanManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'logistics', 'delivery-methods', 'create'));
    }
}
