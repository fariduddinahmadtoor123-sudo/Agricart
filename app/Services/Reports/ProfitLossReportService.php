<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\ExpensePayment;
use App\Models\FundTransfer;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitLossReportService
{
    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     kpis: array<string, int|float|string>,
     *     generated_at: string
     * }
     */
    public function build(
        ?string $search = null,
        ?string $periodFilter = null,
        string $resultFilter = 'all',
    ): array {
        $resultFilter = in_array($resultFilter, ['all', 'profit', 'loss'], true)
            ? $resultFilter
            : 'all';

        [$start, $end] = $this->periodRange($periodFilter);

        $salesQuery = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->with(['customer']);
        $this->applyDateRange($salesQuery, 'sale_date', $start, $end);

        if (filled($search)) {
            $needle = trim($search);
            $salesQuery->where(function (Builder $query) use ($needle): void {
                $query->where('sale_number', 'like', '%'.$needle.'%')
                    ->orWhereHas('customer', function (Builder $customer) use ($needle): void {
                        $customer->where('display_name', 'like', '%'.$needle.'%')
                            ->orWhere('name_en', 'like', '%'.$needle.'%')
                            ->orWhere('name_ur', 'like', '%'.$needle.'%')
                            ->orWhere('first_name', 'like', '%'.$needle.'%')
                            ->orWhere('last_name', 'like', '%'.$needle.'%')
                            ->orWhere('phone', 'like', '%'.$needle.'%');
                    });
            });
        }

        /** @var Collection<int, Sale> $sales */
        $sales = $salesQuery->orderByDesc('sale_date')->orderByDesc('id')->get();

        $saleIds = $sales->pluck('id')->all();
        $unitCostHistory = $this->unitCostHistory();
        $itemsBySale = $this->saleItemsBySale($saleIds);
        $returnsBySale = $this->returnsBySale($saleIds);
        $returnQtyBySaleProduct = $this->returnQtyBySaleProduct($saleIds);

        $rows = [];
        $sumNet = 0.0;
        $sumCogs = 0.0;
        $sumProfit = 0.0;
        $profitCount = 0;
        $lossCount = 0;

        foreach ($sales as $sale) {
            $saleId = (int) $sale->id;
            $saleDate = $sale->sale_date?->toDateString() ?? '';
            $gross = $this->numeric($sale->total_amount);
            $returns = $returnsBySale[$saleId] ?? 0.0;
            $net = $gross - $returns;
            $cogs = $this->invoiceCogs(
                items: $itemsBySale[$saleId] ?? collect(),
                returnQtyByProduct: $returnQtyBySaleProduct[$saleId] ?? [],
                saleDate: $saleDate,
                history: $unitCostHistory,
            );
            $profit = $net - $cogs;
            $result = $this->resultKey($profit);

            if ($resultFilter === 'profit' && $result !== 'profit') {
                continue;
            }

            if ($resultFilter === 'loss' && $result !== 'loss') {
                continue;
            }

            $sumNet += $net;
            $sumCogs += $cogs;
            $sumProfit += $profit;

            if ($result === 'profit') {
                $profitCount++;
            } elseif ($result === 'loss') {
                $lossCount++;
            }

            $customer = $sale->customer;
            $customerLabel = $customer instanceof Customer
                ? ($customer->displayLabel() ?: __('reports.sales.unknown_customer'))
                : __('reports.sales.walk_in');

            $margin = $net > 0 ? ($profit / $net) * 100 : 0.0;

            $rows[] = [
                'id' => $saleId,
                'invoice' => (string) $sale->sale_number,
                'date' => $saleDate !== '' ? $saleDate : '—',
                'customer' => $customerLabel,
                'gross_sales' => $this->formatMoney($gross),
                'returns' => $this->formatMoney($returns),
                'net_sales' => $this->formatMoney($net),
                'cogs' => $this->formatMoney($cogs),
                'profit' => $this->formatMoney($profit),
                'profit_numeric' => $profit,
                'margin' => $this->formatPercent($margin),
                'result' => $result,
                'result_label' => __('reports.profit.result.'.$result),
            ];
        }

        $expenses = $this->operatingExpenses($start, $end);
        $netProfit = $sumProfit - $expenses;
        $margin = $sumNet > 0 ? ($sumProfit / $sumNet) * 100 : 0.0;

        return [
            'rows' => $rows,
            'kpis' => [
                'invoices' => count($rows),
                'net_sales' => $this->formatMoney($sumNet),
                'cogs' => $this->formatMoney($sumCogs),
                'gross_profit' => $this->formatMoney($sumProfit),
                'expenses' => $this->formatMoney($expenses),
                'net_profit' => $this->formatMoney($netProfit),
                'margin' => $this->formatPercent($margin),
                'profit_count' => $profitCount,
                'loss_count' => $lossCount,
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
            'invoice' => __('reports.columns.invoice'),
            'date' => __('reports.columns.date'),
            'customer' => __('reports.columns.customer'),
            'gross_sales' => __('reports.columns.gross_sales'),
            'returns' => __('reports.columns.returns'),
            'net_sales' => __('reports.columns.net_sales'),
            'cogs' => __('reports.columns.cogs'),
            'profit' => __('reports.columns.profit'),
            'margin' => __('reports.columns.margin'),
            'result' => __('reports.columns.result'),
        ];

        $columns = array_values(array_filter(
            $visibleColumns,
            fn (string $col): bool => isset($labels[$col]),
        ));

        if ($columns === []) {
            $columns = ['invoice', 'date', 'customer', 'net_sales', 'cogs', 'profit', 'result'];
        }

        $matrix = [array_map(fn (string $col): string => $labels[$col], $columns)];

        foreach ($rows as $row) {
            $matrix[] = array_map(function (string $col) use ($row): string {
                if ($col === 'result') {
                    return (string) ($row['result_label'] ?? $row['result'] ?? '');
                }

                return (string) ($row[$col] ?? '');
            }, $columns);
        }

        return $matrix;
    }

    /**
     * @param  Collection<int, SaleItem>  $items
     * @param  array<int, float>  $returnQtyByProduct
     * @param  array<int, list<array{date: string, cost: float}>>  $history
     */
    private function invoiceCogs(
        Collection $items,
        array $returnQtyByProduct,
        string $saleDate,
        array $history,
    ): float {
        $cogs = 0.0;
        $returnedUsed = [];

        foreach ($items as $item) {
            $productId = (int) ($item->product_id ?? 0);
            $qty = $this->numeric($item->quantity);
            $returned = 0.0;

            if ($productId > 0) {
                $availableReturn = $returnQtyByProduct[$productId] ?? 0.0;
                $already = $returnedUsed[$productId] ?? 0.0;
                $returned = min($qty, max(0.0, $availableReturn - $already));
                $returnedUsed[$productId] = $already + $returned;
            }

            $netQty = max(0.0, $qty - $returned);
            $unitCost = $productId > 0 ? $this->unitCostAsOf($history, $productId, $saleDate) : 0.0;
            $cogs += $netQty * $unitCost;
        }

        return max(0.0, $cogs);
    }

    /**
     * @param  list<int>  $saleIds
     * @return array<int, Collection<int, SaleItem>>
     */
    private function saleItemsBySale(array $saleIds): array
    {
        if ($saleIds === []) {
            return [];
        }

        return SaleItem::query()
            ->whereIn('sale_id', $saleIds)
            ->get(['id', 'sale_id', 'product_id', 'quantity'])
            ->groupBy(fn (SaleItem $item): int => (int) $item->sale_id)
            ->all();
    }

    /**
     * @param  list<int>  $saleIds
     * @return array<int, float>
     */
    private function returnsBySale(array $saleIds): array
    {
        if ($saleIds === []) {
            return [];
        }

        return SaleReturn::query()
            ->whereIn('sale_id', $saleIds)
            ->selectRaw('sale_id')
            ->selectRaw('COALESCE(SUM(refund_total), 0) as returns_total')
            ->groupBy('sale_id')
            ->pluck('returns_total', 'sale_id')
            ->map(fn ($value): float => $this->numeric($value))
            ->all();
    }

    /**
     * @param  list<int>  $saleIds
     * @return array<int, array<int, float>>
     */
    private function returnQtyBySaleProduct(array $saleIds): array
    {
        if ($saleIds === []) {
            return [];
        }

        $rows = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->whereIn('sale_returns.sale_id', $saleIds)
            ->whereNotNull('sale_return_items.product_id')
            ->selectRaw('sale_returns.sale_id as sale_id')
            ->selectRaw('sale_return_items.product_id as product_id')
            ->selectRaw($this->qtySumExpression('sale_return_items.return_quantity').' as qty')
            ->groupBy('sale_returns.sale_id', 'sale_return_items.product_id')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $saleId = (int) $row->sale_id;
            $productId = (int) $row->product_id;
            $map[$saleId][$productId] = $this->numeric($row->qty);
        }

        return $map;
    }

    /**
     * Purchase cost timeline per product (newest first).
     *
     * @return array<int, list<array{date: string, cost: float}>>
     */
    private function unitCostHistory(): array
    {
        $rows = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereNotNull('purchase_items.product_id')
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchase_items.id')
            ->get([
                'purchase_items.product_id',
                'purchases.purchase_date',
                'purchase_items.cost_price',
                'purchase_items.current_rate',
            ]);

        $history = [];

        foreach ($rows as $row) {
            $productId = (int) $row->product_id;

            if ($productId <= 0) {
                continue;
            }

            $cost = $this->numeric($row->cost_price);
            if ($cost <= 0) {
                $cost = $this->numeric($row->current_rate);
            }

            if ($cost <= 0) {
                continue;
            }

            $date = $row->purchase_date instanceof \DateTimeInterface
                ? $row->purchase_date->format('Y-m-d')
                : (string) $row->purchase_date;

            $history[$productId][] = [
                'date' => $date,
                'cost' => $cost,
            ];
        }

        return $history;
    }

    /**
     * @param  array<int, list<array{date: string, cost: float}>>  $history
     */
    private function unitCostAsOf(array $history, int $productId, string $saleDate): float
    {
        $entries = $history[$productId] ?? [];

        if ($entries === []) {
            return 0.0;
        }

        foreach ($entries as $entry) {
            if ($saleDate === '' || $entry['date'] <= $saleDate) {
                return $entry['cost'];
            }
        }

        // Sale before any purchase on record — use earliest known cost.
        return $entries[array_key_last($entries)]['cost'];
    }

    private function resultKey(float $profit): string
    {
        if ($profit > 0.004) {
            return 'profit';
        }

        if ($profit < -0.004) {
            return 'loss';
        }

        return 'even';
    }

    /**
     * Operating expenses for the period:
     * - Structured Finance → Payments (`expense_payments`)
     * - Fund Transfer purchaser / payee payments (legacy type `expense_payment`)
     *
     * Both reduce net profit. They are separate cash paths and are not double-counted.
     */
    private function operatingExpenses(?CarbonImmutable $start, ?CarbonImmutable $end): float
    {
        $structured = ExpensePayment::query();
        $this->applyDateRange($structured, 'payment_date', $start, $end);

        $purchaserPays = FundTransfer::query()
            ->where('transaction_type', FundTransfer::TYPE_EXPENSE_PAYMENT);
        $this->applyDateRange($purchaserPays, 'transaction_date', $start, $end);

        return round(
            $this->sumDecimal($structured, 'amount') + $this->sumDecimal($purchaserPays, 'amount'),
            2,
        );
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

    /**
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    private function periodRange(?string $period): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'month' => [$today->startOfMonth(), $today->endOfMonth()],
            'quarter' => [$today->startOfQuarter(), $today->endOfQuarter()],
            'year' => [$today->startOfYear(), $today->endOfYear()],
            default => [null, null],
        };
    }

    private function qtySumExpression(string $column): string
    {
        $cast = DB::connection()->getDriverName() === 'sqlite' ? 'REAL' : 'DECIMAL(14,4)';

        return "COALESCE(SUM(CAST({$column} AS {$cast})), 0)";
    }

    private function sumDecimal(Builder $query, string $column): float
    {
        return (float) $query->sum($column);
    }

    private function numeric(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 1, '.', '').'%';
    }
}
