<?php

namespace App\Services\Sales;

use App\Filament\Clusters\Sales\OnlineOrdersPage;
use App\Filament\Clusters\Sales\PosSalesPage;
use App\Filament\Clusters\Sales\SalesQuotationsPage;
use App\Filament\Clusters\Sales\SalesReturnsPage;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleQuotation;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Builder;

class SalesSearchService
{
    /**
     * @return array<string, list<array{label: string, meta: string|null, url: string}>>
     */
    public function search(string $term, int $limitPerGroup = 6): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%' . $term . '%';

        return array_filter([
            'pos_sales' => PosSalesPage::canAccess()
                ? $this->mapSales($needle, $limitPerGroup)
                : [],
            'sales_quotations' => SalesQuotationsPage::canAccess()
                ? $this->mapQuotations($needle, $limitPerGroup)
                : [],
            'sales_returns' => SalesReturnsPage::canAccess()
                ? $this->mapReturns($needle, $limitPerGroup)
                : [],
            'online_orders' => OnlineOrdersPage::canAccess()
                ? $this->mapOnlineOrders($needle, $limitPerGroup)
                : [],
        ], fn (array $items): bool => $items !== []);
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapSales(string $needle, int $limit): array
    {
        $url = PosSalesPage::getUrl();

        return Sale::query()
            ->with(['customer'])
            ->where(function (Builder $query) use ($needle): void {
                $query->where('sale_number', 'like', $needle)
                    ->orWhereHas('customer', function (Builder $customer) use ($needle): void {
                        $customer->where('display_name', 'like', $needle)
                            ->orWhere('customer_number', 'like', $needle)
                            ->orWhere('phone', 'like', $needle);
                    });
            })
            ->orderByDesc('sale_date')
            ->limit($limit)
            ->get()
            ->map(fn (Sale $sale): array => [
                'label' => (string) $sale->sale_number,
                'meta' => $sale->customer?->displayLabel(),
                'url' => $url,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapQuotations(string $needle, int $limit): array
    {
        $url = SalesQuotationsPage::getUrl();

        return SaleQuotation::query()
            ->with(['customer'])
            ->where(function (Builder $query) use ($needle): void {
                $query->where('quotation_number', 'like', $needle)
                    ->orWhereHas('customer', function (Builder $customer) use ($needle): void {
                        $customer->where('display_name', 'like', $needle)
                            ->orWhere('customer_number', 'like', $needle);
                    });
            })
            ->orderByDesc('quotation_date')
            ->limit($limit)
            ->get()
            ->map(fn (SaleQuotation $quotation): array => [
                'label' => (string) $quotation->quotation_number,
                'meta' => $quotation->customer?->displayLabel(),
                'url' => $url,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapReturns(string $needle, int $limit): array
    {
        $url = SalesReturnsPage::getUrl();

        return SaleReturn::query()
            ->with(['customer', 'sale'])
            ->where(function (Builder $query) use ($needle): void {
                $query->where('return_number', 'like', $needle)
                    ->orWhereHas('sale', fn (Builder $sale) => $sale->where('sale_number', 'like', $needle))
                    ->orWhereHas('customer', function (Builder $customer) use ($needle): void {
                        $customer->where('display_name', 'like', $needle)
                            ->orWhere('customer_number', 'like', $needle);
                    });
            })
            ->orderByDesc('return_date')
            ->limit($limit)
            ->get()
            ->map(fn (SaleReturn $return): array => [
                'label' => (string) $return->return_number,
                'meta' => $return->sale?->sale_number,
                'url' => $url,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapOnlineOrders(string $needle, int $limit): array
    {
        $url = OnlineOrdersPage::getUrl();

        return OnlineSaleOrder::query()
            ->with(['customer'])
            ->where(function (Builder $query) use ($needle): void {
                $query->where('order_number', 'like', $needle)
                    ->orWhereHas('customer', function (Builder $customer) use ($needle): void {
                        $customer->where('display_name', 'like', $needle)
                            ->orWhere('customer_number', 'like', $needle)
                            ->orWhere('phone', 'like', $needle);
                    });
            })
            ->orderByDesc('order_date')
            ->limit($limit)
            ->get()
            ->map(fn (OnlineSaleOrder $order): array => [
                'label' => (string) $order->order_number,
                'meta' => $order->customer?->displayLabel(),
                'url' => $url,
            ])
            ->all();
    }
}
