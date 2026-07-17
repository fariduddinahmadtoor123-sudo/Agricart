<?php

namespace App\Services\Reports;

use App\Models\PurchaseItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SeasonalPurchasesReportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, kpis: array<string, int|string>, generated_at: string}
     */
    public function build(
        ?string $search = null,
        ?string $periodFilter = null,
        string $supplierFilter = 'all',
    ): array {
        [$start, $end] = $this->periodRange($periodFilter);

        $items = PurchaseItem::query()
            ->with(['purchase.supplier'])
            ->whereHas('purchase', function (Builder $query) use ($start, $end, $supplierFilter): void {
                $this->applyDateRange($query, 'purchase_date', $start, $end);

                if ($supplierFilter === 'active') {
                    $query->whereHas('supplier', fn (Builder $supplier): Builder => $supplier->where('status', 'active'));
                }
            })
            ->when(filled($search), function (Builder $query) use ($search): void {
                $needle = '%'.trim((string) $search).'%';
                $query->where(function (Builder $inner) use ($needle): void {
                    $inner->where('product_name_en', 'like', $needle)
                        ->orWhere('product_name_ur', 'like', $needle)
                        ->orWhereHas('purchase.supplier', function (Builder $supplier) use ($needle): void {
                            $supplier->where('business_name_en', 'like', $needle)
                                ->orWhere('business_name_ur', 'like', $needle);
                        });
                });
            })
            ->get();

        /** @var array<string, array<string, mixed>> $groups */
        $groups = [];

        foreach ($items as $item) {
            $date = $item->purchase?->purchase_date;

            if ($date === null) {
                continue;
            }

            $supplierId = $item->purchase?->supplier_id;
            $key = $this->groupKey($date, $item->product_id, $supplierId, $item->product_name_en);
            $groups[$key] ??= [
                'season' => $this->seasonLabel(CarbonImmutable::parse($date)),
                'product' => $item->displayName() ?: __('reports.sales.unknown_product'),
                'supplier' => $item->purchase?->supplier?->displayBusinessName() ?: '—',
                'qty_raw' => 0.0,
                'value_raw' => 0.0,
            ];
            $groups[$key]['qty_raw'] += (float) $item->purchase_qty;
            $groups[$key]['value_raw'] += (float) $item->line_total;
        }

        krsort($groups);
        $rows = [];
        $totalQty = 0.0;
        $totalValue = 0.0;

        foreach (array_values($groups) as $index => $group) {
            $qty = round((float) $group['qty_raw'], 3);
            $value = round((float) $group['value_raw'], 2);
            $totalQty += $qty;
            $totalValue += $value;
            $rows[] = [
                'id' => $index + 1,
                'season' => $group['season'],
                'product' => $group['product'],
                'qty_purchased' => $this->formatQuantity($qty),
                'purchase_value' => $this->formatMoney($value),
                'supplier' => $group['supplier'],
            ];
        }

        return [
            'rows' => $rows,
            'kpis' => [
                'lines' => count($rows),
                'quantity' => $this->formatQuantity($totalQty),
                'purchase_value' => $this->formatMoney($totalValue),
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
            'qty_purchased' => __('reports.columns.qty_purchased'),
            'purchase_value' => __('reports.columns.purchase_value'),
            'supplier' => __('reports.columns.supplier'),
        ];
        $columns = array_values(array_filter($visibleColumns, fn (string $column): bool => isset($labels[$column])));
        $matrix = [array_map(fn (string $column): string => $labels[$column], $columns)];

        foreach ($rows as $row) {
            $matrix[] = array_map(fn (string $column): string => (string) ($row[$column] ?? ''), $columns);
        }

        return $matrix;
    }

    private function groupKey(mixed $date, ?int $productId, ?int $supplierId, ?string $productName): string
    {
        $date = CarbonImmutable::parse($date);

        return $date->format('Y').'-Q'.$date->quarter.'-'.($productId ?? md5((string) $productName)).'-'.($supplierId ?? 0);
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
