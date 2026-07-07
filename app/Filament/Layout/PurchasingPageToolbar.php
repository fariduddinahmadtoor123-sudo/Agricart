<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Purchasing\OverviewPage;
use App\Services\Purchasing\PurchasePlanningService;
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
            'purchase-planning' => [
                ...$config,
                'livewirePrefix' => 'purchase-planning',
                'columnDataAttr' => 'data-planning-column',
                'interactive' => true,
                'formKey' => 'purchase-planning',
                'addLabel' => static::userCan('create') ? __('purchase_planning.actions.add') : null,
                'compactAddButton' => true,
                'filters' => static::planningFilters(),
                'columns' => [
                    ['label' => __('purchase_planning.list.col_number'), 'value' => 'planning_number', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_items'), 'value' => 'item_count', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_paper'), 'value' => 'paper_size', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_locale'), 'value' => 'locale', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_created_by'), 'value' => 'created_by', 'visible' => true],
                    ['label' => __('purchase_planning.list.col_created'), 'value' => 'created_at', 'visible' => true],
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
                'filters' => static::purchaseFilters(),
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
            default => $config,
        };
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function purchaseFilters(): array
    {
        $filters = [];

        foreach (PurchaseService::DELIVERY_STATUSES as $status) {
            $filters[] = [
                'label' => __('purchases.statuses.delivery.' . $status),
                'value' => 'delivery:' . $status,
            ];
        }

        foreach (PurchaseService::PAYMENT_STATUSES as $status) {
            $filters[] = [
                'label' => __('purchases.statuses.payment.' . $status),
                'value' => 'payment:' . $status,
            ];
        }

        foreach (PurchaseService::DISPUTE_STATUSES as $status) {
            $filters[] = [
                'label' => __('purchases.statuses.dispute.' . $status),
                'value' => 'dispute:' . $status,
            ];
        }

        return $filters;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function planningFilters(): array
    {
        $filters = [];

        foreach (PurchasePlanningService::PAPER_SIZES as $size) {
            $filters[] = [
                'label' => __('purchase_planning.paper_sizes.' . $size),
                'value' => 'paper_size:' . $size,
            ];
        }

        foreach (PurchasePlanningService::LOCALES as $locale) {
            $filters[] = [
                'label' => __('purchase_planning.locales.' . $locale),
                'value' => 'locale:' . $locale,
            ];
        }

        return $filters;
    }
}
