<?php

namespace App\Services\Logistics;

use App\Filament\Clusters\Logistics\DeliveryMethodsPage;
use App\Filament\Clusters\Logistics\ShipmentsPage;
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
        $pending = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->where(function ($query): void {
                $query->whereNull('fulfillment_status')
                    ->orWhere('fulfillment_status', '!=', OnlineSaleOrder::FULFILLMENT_DELIVERED);
            })
            ->count();

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
        ];
    }
}
