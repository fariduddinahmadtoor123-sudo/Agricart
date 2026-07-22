<?php

namespace App\Services\Logistics;

use App\Filament\Clusters\Logistics\DeliveryMethodsPage;
use App\Filament\Clusters\Logistics\ShipmentsPage;
use App\Filament\Clusters\Logistics\ShippedOrdersPage;
use App\Models\DeliveryMethod;
use App\Models\OnlineSaleOrder;

class LogisticsOverviewService
{
    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        $total = DeliveryMethod::query()->count();
        $active = DeliveryMethod::query()->where('status', DeliveryMethod::STATUS_ACTIVE)->count();
        $pending = OnlineSaleOrder::query()->logisticsPending()->count();
        $shipped = OnlineSaleOrder::query()->logisticsShipped()->count();

        return [
            [
                'key' => 'delivery_methods',
                'label' => __('agricart.logistics.overview.cards.delivery_methods'),
                'count' => $total,
                'active' => $active,
                'url' => DeliveryMethodsPage::getUrl(),
            ],
            [
                'key' => 'pending_shipments',
                'label' => __('agricart.logistics.overview.cards.pending_shipments'),
                'count' => $pending,
                'active' => null,
                'url' => ShipmentsPage::getUrl(),
            ],
            [
                'key' => 'shipped_orders',
                'label' => __('agricart.logistics.overview.cards.shipped_orders'),
                'count' => $shipped,
                'active' => null,
                'url' => ShippedOrdersPage::getUrl(),
            ],
        ];
    }
}
