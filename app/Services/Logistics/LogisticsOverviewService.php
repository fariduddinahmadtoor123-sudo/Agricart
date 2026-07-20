<?php

namespace App\Services\Logistics;

use App\Filament\Clusters\Logistics\DeliveryMethodsPage;
use App\Models\DeliveryMethod;

class LogisticsOverviewService
{
    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        $total = DeliveryMethod::query()->count();
        $active = DeliveryMethod::query()->where('status', DeliveryMethod::STATUS_ACTIVE)->count();

        return [
            [
                'key' => 'delivery_methods',
                'label' => __('agricart.logistics.overview.cards.delivery_methods'),
                'count' => $total,
                'active' => $active,
                'url' => DeliveryMethodsPage::getUrl(),
            ],
        ];
    }
}
