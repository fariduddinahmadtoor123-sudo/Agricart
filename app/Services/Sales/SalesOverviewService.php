<?php

namespace App\Services\Sales;

use App\Filament\Clusters\Sales\OnlineOrdersPage;
use App\Filament\Clusters\Sales\OrderDispatchPage;
use App\Filament\Clusters\Sales\PosSalesPage;
use App\Filament\Clusters\Sales\SalesQuotationsPage;
use App\Filament\Clusters\Sales\SalesReturnsPage;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleQuotation;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Builder;

class SalesOverviewService
{
    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        $cards = [
            $this->posSalesCard(),
            $this->quotationsCard(),
            $this->returnsCard(),
            $this->onlineOrdersCard(),
            $this->orderDispatchCard(),
        ];

        $visible = array_filter($cards, function (array $card): bool {
            $pageClass = $card['page'];

            return $pageClass::canAccess();
        });

        return array_values(array_map(function (array $card): array {
            unset($card['page']);

            return $card;
        }, $visible));
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function posSalesCard(): array
    {
        $query = Sale::query();
        $count = (clone $query)->count();
        $held = (clone $query)->where('status', Sale::STATUS_HELD)->count();

        return $this->card('pos_sales', $count, $held, PosSalesPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function quotationsCard(): array
    {
        $count = SaleQuotation::query()->count();

        return $this->card('sales_quotations', $count, null, SalesQuotationsPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function returnsCard(): array
    {
        $query = SaleReturn::query();
        $count = (clone $query)->count();
        $unpaid = (clone $query)->where('payment_status', 'unpaid')->count();

        return $this->card('sales_returns', $count, $unpaid, SalesReturnsPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function onlineOrdersCard(): array
    {
        $query = OnlineSaleOrder::query();
        $count = (clone $query)->count();
        $open = (clone $query)
            ->where('order_status', OnlineSaleOrder::STATUS_PENDING)
            ->count();

        return $this->card('online_orders', $count, $open, OnlineOrdersPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function orderDispatchCard(): array
    {
        $query = $this->dispatchQueueQuery();
        $count = (clone $query)->count();
        $awaiting = (clone $query)
            ->where('fulfillment_status', OnlineSaleOrder::FULFILLMENT_AWAITING)
            ->count();

        return $this->card('order_dispatch', $count, $awaiting, OrderDispatchPage::class);
    }

    /**
     * @return Builder<OnlineSaleOrder>
     */
    private function dispatchQueueQuery(): Builder
    {
        return OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at');
    }

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function card(string $key, int $count, ?int $active, string $pageClass): array
    {
        return [
            'key' => $key,
            'label' => (string) __('agricart.sales.overview.cards.' . $key),
            'count' => $count,
            'active' => $active,
            'url' => $pageClass::getUrl(),
            'page' => $pageClass,
        ];
    }
}
