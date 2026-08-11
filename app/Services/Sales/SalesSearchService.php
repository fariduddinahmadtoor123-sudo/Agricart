<?php

namespace App\Services\Sales;

use App\Filament\Clusters\Sales\OnlineQuotationsPage;
use App\Filament\Clusters\Sales\OrderDispatchPage;
use App\Filament\Clusters\Sales\PosSalesPage;
use App\Filament\Clusters\Sales\SalesQuotationsPage;
use App\Filament\Clusters\Sales\SalesReturnsPage;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleQuotation;
use App\Models\SaleReturn;
use App\Support\Search\SearchHit;
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
            'online_orders' => (OnlineQuotationsPage::canAccess() || OrderDispatchPage::canAccess())
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
            ->map(function (Sale $sale) use ($url): array {
                $label = (string) $sale->sale_number;

                return SearchHit::make(
                    $label,
                    $sale->customer?->displayLabel(),
                    $url,
                    $sale->customer?->profilePhotoUrl(),
                    [
                        'formKey' => 'sales',
                        'saleId' => $sale->id,
                        'title' => $label,
                        'resource' => PosSalesPage::getNavigationLabel(),
                    ],
                );
            })
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
            ->map(function (SaleQuotation $quotation) use ($url): array {
                $label = (string) $quotation->quotation_number;

                return SearchHit::make(
                    $label,
                    $quotation->customer?->displayLabel(),
                    $url,
                    $quotation->customer?->profilePhotoUrl(),
                    [
                        'formKey' => 'sale-quotations',
                        'quotationId' => $quotation->id,
                        'title' => $label,
                        'resource' => __('agricart.nav.sales.sales-quotations'),
                    ],
                );
            })
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
            ->map(function (SaleReturn $return) use ($url): array {
                $label = (string) $return->return_number;

                return SearchHit::make(
                    $label,
                    $return->sale?->sale_number,
                    $url,
                    $return->customer?->profilePhotoUrl(),
                    [
                        'formKey' => 'sale-returns',
                        'returnId' => $return->id,
                        'title' => $label,
                        'resource' => __('agricart.nav.sales.sales-returns'),
                    ],
                );
            })
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapOnlineOrders(string $needle, int $limit): array
    {
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
            ->map(function (OnlineSaleOrder $order): array {
                $inQueue = $order->order_status === OnlineSaleOrder::STATUS_CONVERTED
                    && $order->queued_at !== null
                    && OrderDispatchPage::canAccess();

                $label = (string) $order->order_number;
                $converted = $order->order_status === OnlineSaleOrder::STATUS_CONVERTED
                    && $order->queued_at === null;
                $context = $inQueue ? 'queue' : ($converted ? 'invoice' : 'quotation');

                return SearchHit::make(
                    $label,
                    $order->customer?->displayLabel(),
                    $inQueue
                        ? OrderDispatchPage::getUrl()
                        : OnlineQuotationsPage::getUrl(),
                    $order->customer?->profilePhotoUrl(),
                    [
                        'formKey' => 'online-orders',
                        'orderId' => $order->id,
                        'mode' => 'view',
                        'onlineOrderContext' => $context,
                        'title' => $label,
                        'resource' => __('agricart.pages.sales.online-orders'),
                    ],
                );
            })
            ->all();
    }
}
