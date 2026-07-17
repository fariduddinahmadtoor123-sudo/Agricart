<?php

namespace App\Services\Reports;

use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SeasonalSalesReportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, kpis: array<string, int|string>, generated_at: string}
     */
    public function build(
        ?string $search = null,
        ?string $periodFilter = null,
        string $channelFilter = 'all',
    ): array {
        [$start, $end] = $this->periodRange($periodFilter);
        $onlineSaleIds = OnlineSaleOrder::query()
            ->whereNotNull('sale_id')
            ->pluck('sale_id');

        $items = SaleItem::query()
            ->with('sale')
            ->whereHas('sale', function (Builder $query) use ($start, $end, $channelFilter, $onlineSaleIds): void {
                $query->where('status', Sale::STATUS_COMPLETED);
                $this->applyDateRange($query, 'sale_date', $start, $end);

                if ($channelFilter === 'online') {
                    $query->whereIn('id', $onlineSaleIds);
                } elseif ($channelFilter === 'pos') {
                    $query->whereNotIn('id', $onlineSaleIds);
                }
            })
            ->when(filled($search), function (Builder $query) use ($search): void {
                $needle = '%'.trim((string) $search).'%';
                $query->where(function (Builder $inner) use ($needle): void {
                    $inner->where('product_name_en', 'like', $needle)
                        ->orWhere('product_name_ur', 'like', $needle);
                });
            })
            ->get();

        $returns = SaleReturnItem::query()
            ->with(['saleReturn.sale'])
            ->whereHas('saleReturn', function (Builder $query) use ($start, $end): void {
                $this->applyDateRange($query, 'return_date', $start, $end);
            })
            ->when(filled($search), function (Builder $query) use ($search): void {
                $needle = '%'.trim((string) $search).'%';
                $query->where(function (Builder $inner) use ($needle): void {
                    $inner->where('product_name_en', 'like', $needle)
                        ->orWhere('product_name_ur', 'like', $needle);
                });
            })
            ->get()
            ->filter(function (SaleReturnItem $item) use ($channelFilter, $onlineSaleIds): bool {
                $saleId = $item->saleReturn?->sale_id;
                $isOnline = $saleId !== null && $onlineSaleIds->contains($saleId);

                return $channelFilter === 'all'
                    || ($channelFilter === 'online' && $isOnline)
                    || ($channelFilter === 'pos' && ! $isOnline);
            });

        /** @var array<string, array<string, mixed>> $groups */
        $groups = [];

        foreach ($items as $item) {
            $date = $item->sale?->sale_date;

            if ($date === null) {
                continue;
            }

            $key = $this->groupKey($date, $item->product_id, $item->product_name_en);
            $groups[$key] ??= $this->emptyGroup($date, $item->product_id, $item->displayName());
            $groups[$key]['qty_raw'] += (float) $item->quantity;
            $groups[$key]['sales_raw'] += (float) $item->line_total;
        }

        foreach ($returns as $item) {
            $date = $item->saleReturn?->return_date;

            if ($date === null) {
                continue;
            }

            $key = $this->groupKey($date, $item->product_id, $item->product_name_en);
            $groups[$key] ??= $this->emptyGroup($date, $item->product_id, $item->displayName());
            $groups[$key]['qty_raw'] -= (float) $item->return_quantity;
            $groups[$key]['sales_raw'] -= (float) $item->return_amount;
        }

        krsort($groups);
        $rows = [];
        $totalQty = 0.0;
        $totalSales = 0.0;

        foreach (array_values($groups) as $index => $group) {
            $qty = round((float) $group['qty_raw'], 3);
            $sales = round((float) $group['sales_raw'], 2);
            $totalQty += $qty;
            $totalSales += $sales;
            $rows[] = [
                'id' => $index + 1,
                'season' => $group['season'],
                'product' => $group['product'],
                'qty_sold' => $this->formatQuantity($qty),
                'net_sales' => $this->formatMoney($sales),
                'avg_price' => $this->formatMoney($qty > 0 ? $sales / $qty : 0),
            ];
        }

        return [
            'rows' => $rows,
            'kpis' => [
                'products' => count($rows),
                'quantity' => $this->formatQuantity($totalQty),
                'net_sales' => $this->formatMoney($totalSales),
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $visibleColumns
     * @return list<list<string>>
     */
    public function csvMatrix(array $rows, array $visibleColumns): array
    {
        $labels = [
            'season' => __('reports.columns.season'),
            'product' => __('reports.columns.product'),
            'qty_sold' => __('reports.columns.qty_sold'),
            'net_sales' => __('reports.columns.net_sales'),
            'avg_price' => __('reports.columns.avg_price'),
        ];
        $columns = array_values(array_filter($visibleColumns, fn (string $column): bool => isset($labels[$column])));
        $matrix = [array_map(fn (string $column): string => $labels[$column], $columns)];

        foreach ($rows as $row) {
            $matrix[] = array_map(fn (string $column): string => (string) ($row[$column] ?? ''), $columns);
        }

        return $matrix;
    }

    /**
     * @return array{season: string, product: string, qty_raw: float, sales_raw: float}
     */
    private function emptyGroup(mixed $date, ?int $productId, string $product): array
    {
        $date = CarbonImmutable::parse($date);

        return [
            'season' => $this->seasonLabel($date),
            'product' => $product !== '' ? $product : __('reports.sales.unknown_product'),
            'qty_raw' => 0.0,
            'sales_raw' => 0.0,
        ];
    }

    private function groupKey(mixed $date, ?int $productId, ?string $productName): string
    {
        $date = CarbonImmutable::parse($date);

        return $date->format('Y').'-Q'.$date->quarter.'-'.($productId ?? md5((string) $productName));
    }

    private function seasonLabel(CarbonImmutable $date): string
    {
        $ranges = [1 => 'Jan–Mar', 2 => 'Apr–Jun', 3 => 'Jul–Sep', 4 => 'Oct–Dec'];

        return $date->format('Y').' Q'.$date->quarter.' ('.$ranges[$date->quarter].')';
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private function periodRange(?string $periodFilter): array
    {
        $today = CarbonImmutable::today();

        return match ($periodFilter) {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'month' => [$today->startOfMonth(), $today->endOfMonth()],
            'quarter' => [$today->firstOfQuarter(), $today->lastOfQuarter()],
            'year' => [$today->startOfYear(), $today->endOfYear()],
            default => [null, null],
        };
    }

    private function applyDateRange(Builder $query, string $column, ?CarbonImmutable $start, ?CarbonImmutable $end): void
    {
        if ($start !== null) {
            $query->whereDate($column, '>=', $start->toDateString());
        }

        if ($end !== null) {
            $query->whereDate($column, '<=', $end->toDateString());
        }
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2).' PKR';
    }

    private function formatQuantity(float $quantity): string
    {
        return number_format($quantity, 3, '.', '');
    }
}
