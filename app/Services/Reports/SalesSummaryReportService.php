<?php

namespace App\Services\Reports;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SalesSummaryReportService
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
        string $channelFilter = 'all',
        string $groupBy = 'day',
    ): array {
        $groupBy = in_array($groupBy, ['day', 'month', 'category', 'customer', 'product'], true)
            ? $groupBy
            : 'day';
        $channelFilter = in_array($channelFilter, ['all', 'pos', 'online'], true)
            ? $channelFilter
            : 'all';

        [$start, $end] = ReportPeriod::range($periodFilter);

        $salesQuery = Sale::query()->where('status', Sale::STATUS_COMPLETED);
        $this->applyDateRange($salesQuery, 'sale_date', $start, $end);
        $this->applyChannel($salesQuery, $channelFilter, 'sales.id');

        $returnsQuery = SaleReturn::query();
        $this->applyDateRange($returnsQuery, 'return_date', $start, $end);
        $this->applyReturnChannel($returnsQuery, $channelFilter);

        $kpiGross = $this->sumDecimal(clone $salesQuery, 'total_amount');
        $kpiInvoices = (clone $salesQuery)->count();
        $kpiReturns = $this->sumDecimal(clone $returnsQuery, 'refund_total');
        $kpiNet = $kpiGross - $kpiReturns;
        $avgTicket = $kpiInvoices > 0 ? $kpiGross / $kpiInvoices : 0.0;

        $rows = match ($groupBy) {
            'month' => $this->rowsByPeriod($salesQuery, $returnsQuery, $search, 'month'),
            'category' => $this->rowsByCategory($start, $end, $channelFilter, $search),
            'customer' => $this->rowsByCustomer($salesQuery, $returnsQuery, $search),
            'product' => $this->rowsByProduct($start, $end, $channelFilter, $search),
            default => $this->rowsByPeriod($salesQuery, $returnsQuery, $search, 'day'),
        };

        return [
            'rows' => $rows,
            'kpis' => [
                'invoices' => $kpiInvoices,
                'gross_sales' => $this->formatMoney($kpiGross),
                'returns' => $this->formatMoney($kpiReturns),
                'net_sales' => $this->formatMoney($kpiNet),
                'avg_ticket' => $this->formatMoney($avgTicket),
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
            'group' => __('reports.columns.group'),
            'period' => __('reports.columns.period'),
            'channel' => __('reports.columns.channel'),
            'category' => __('reports.columns.category'),
            'customer' => __('reports.columns.customer'),
            'product' => __('reports.columns.product'),
            'invoices' => __('reports.columns.invoices'),
            'gross_sales' => __('reports.columns.gross_sales'),
            'returns' => __('reports.columns.returns'),
            'net_sales' => __('reports.columns.net_sales'),
            'avg_ticket' => __('reports.columns.avg_ticket'),
        ];

        $columns = array_values(array_filter(
            $visibleColumns,
            fn (string $col): bool => isset($labels[$col]),
        ));

        if ($columns === []) {
            $columns = ['group', 'invoices', 'gross_sales', 'returns', 'net_sales', 'avg_ticket'];
        }

        $matrix = [array_map(fn (string $col): string => $labels[$col], $columns)];

        foreach ($rows as $row) {
            $matrix[] = array_map(fn (string $col): string => (string) ($row[$col] ?? ''), $columns);
        }

        return $matrix;
    }

    /**
     * @param  Builder<Sale>  $salesQuery
     * @param  Builder<SaleReturn>  $returnsQuery
     * @return list<array<string, mixed>>
     */
    private function rowsByPeriod(Builder $salesQuery, Builder $returnsQuery, ?string $search, string $mode): array
    {
        $driver = DB::connection()->getDriverName();
        $saleExpr = $mode === 'month'
            ? ($driver === 'sqlite' ? "strftime('%Y-%m', sale_date)" : "DATE_FORMAT(sale_date, '%Y-%m')")
            : ($driver === 'sqlite' ? "strftime('%Y-%m-%d', sale_date)" : 'DATE(sale_date)');
        $returnExpr = $mode === 'month'
            ? ($driver === 'sqlite' ? "strftime('%Y-%m', return_date)" : "DATE_FORMAT(return_date, '%Y-%m')")
            : ($driver === 'sqlite' ? "strftime('%Y-%m-%d', return_date)" : 'DATE(return_date)');

        $sales = (clone $salesQuery)
            ->selectRaw("{$saleExpr} as period_key")
            ->selectRaw('COUNT(*) as invoices')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as gross_sales')
            ->groupBy(DB::raw($saleExpr))
            ->orderBy('period_key')
            ->get()
            ->keyBy('period_key');

        $returns = (clone $returnsQuery)
            ->selectRaw("{$returnExpr} as period_key")
            ->selectRaw('COALESCE(SUM(refund_total), 0) as returns_total')
            ->groupBy(DB::raw($returnExpr))
            ->get()
            ->keyBy('period_key');

        $keys = $sales->keys()->merge($returns->keys())->unique()->sort()->values();
        $rows = [];

        foreach ($keys as $key) {
            $label = (string) $key;
            if (filled($search) && ! str_contains(mb_strtolower($label), mb_strtolower(trim($search)))) {
                continue;
            }

            $gross = $this->numeric($sales->get($key)?->gross_sales ?? 0);
            $invoices = (int) ($sales->get($key)?->invoices ?? 0);
            $refunds = $this->numeric($returns->get($key)?->returns_total ?? 0);
            $net = $gross - $refunds;

            $rows[] = $this->makeRow(
                id: (string) $key,
                group: $label,
                invoices: $invoices,
                gross: $gross,
                returns: $refunds,
                net: $net,
                extras: ['period' => $label],
            );
        }

        return $rows;
    }

    /**
     * @param  Builder<Sale>  $salesQuery
     * @param  Builder<SaleReturn>  $returnsQuery
     * @return list<array<string, mixed>>
     */
    private function rowsByCustomer(Builder $salesQuery, Builder $returnsQuery, ?string $search): array
    {
        $sales = (clone $salesQuery)
            ->selectRaw('COALESCE(customer_id, 0) as party_id')
            ->selectRaw('COUNT(*) as invoices')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as gross_sales')
            ->groupBy('party_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->party_id);

        $returns = (clone $returnsQuery)
            ->selectRaw('COALESCE(customer_id, 0) as party_id')
            ->selectRaw('COALESCE(SUM(refund_total), 0) as returns_total')
            ->groupBy('party_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->party_id);

        $ids = $sales->keys()->merge($returns->keys())->unique()->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values();
        $customers = Customer::query()->whereIn('id', $ids)->get()->keyBy('id');

        $keys = $sales->keys()->merge($returns->keys())->unique()->sort()->values();
        $rows = [];

        foreach ($keys as $key) {
            $partyId = (int) $key;
            $label = $partyId > 0
                ? ($customers->get($partyId)?->displayLabel() ?: __('reports.sales.unknown_customer'))
                : __('reports.sales.walk_in');

            if (filled($search) && ! str_contains(mb_strtolower($label), mb_strtolower(trim($search)))) {
                continue;
            }

            $gross = $this->numeric($sales->get($key)?->gross_sales ?? 0);
            $invoices = (int) ($sales->get($key)?->invoices ?? 0);
            $refunds = $this->numeric($returns->get($key)?->returns_total ?? 0);

            $rows[] = $this->makeRow(
                id: (string) $partyId,
                group: $label,
                invoices: $invoices,
                gross: $gross,
                returns: $refunds,
                net: $gross - $refunds,
                extras: ['customer' => $label],
            );
        }

        usort($rows, fn (array $a, array $b): int => ($b['gross_sales_numeric'] <=> $a['gross_sales_numeric']));

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsByCategory(?CarbonImmutable $start, ?CarbonImmutable $end, string $channel, ?string $search): array
    {
        $saleItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', Sale::STATUS_COMPLETED);
        $this->applyDateRange($saleItems, 'sales.sale_date', $start, $end);
        $this->applyChannel($saleItems, $channel, 'sales.id');

        $lineSum = $this->lineTotalSumExpression('sale_items.line_total');

        $saleRows = $saleItems
            ->selectRaw('COALESCE(products.category_id, 0) as category_id')
            ->selectRaw('COUNT(DISTINCT sales.id) as invoices')
            ->selectRaw("{$lineSum} as gross_sales")
            ->groupBy('category_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->category_id);

        $returnItems = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id');
        $this->applyDateRange($returnItems, 'sale_returns.return_date', $start, $end);
        $this->applyReturnChannelOnJoinedSales($returnItems, $channel);

        $returnSum = $this->lineTotalSumExpression('sale_return_items.return_amount');

        $returnRows = $returnItems
            ->selectRaw('COALESCE(products.category_id, 0) as category_id')
            ->selectRaw("{$returnSum} as returns_total")
            ->groupBy('category_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->category_id);

        $ids = $saleRows->keys()->merge($returnRows->keys())->unique()->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values();
        $categories = Category::query()->whereIn('id', $ids)->get()->keyBy('id');

        $keys = $saleRows->keys()->merge($returnRows->keys())->unique()->values();
        $rows = [];

        foreach ($keys as $key) {
            $categoryId = (int) $key;
            $label = $categoryId > 0
                ? ($this->categoryLabel($categories->get($categoryId)) ?: __('reports.sales.uncategorized'))
                : __('reports.sales.uncategorized');

            if (filled($search) && ! str_contains(mb_strtolower($label), mb_strtolower(trim($search)))) {
                continue;
            }

            $gross = $this->numeric($saleRows->get($key)?->gross_sales ?? 0);
            $invoices = (int) ($saleRows->get($key)?->invoices ?? 0);
            $refunds = $this->numeric($returnRows->get($key)?->returns_total ?? 0);

            $rows[] = $this->makeRow(
                id: (string) $categoryId,
                group: $label,
                invoices: $invoices,
                gross: $gross,
                returns: $refunds,
                net: $gross - $refunds,
                extras: ['category' => $label],
            );
        }

        usort($rows, fn (array $a, array $b): int => ($b['gross_sales_numeric'] <=> $a['gross_sales_numeric']));

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsByProduct(?CarbonImmutable $start, ?CarbonImmutable $end, string $channel, ?string $search): array
    {
        $saleItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED);
        $this->applyDateRange($saleItems, 'sales.sale_date', $start, $end);
        $this->applyChannel($saleItems, $channel, 'sales.id');

        $lineSum = $this->lineTotalSumExpression('sale_items.line_total');

        $saleRows = $saleItems
            ->selectRaw('COALESCE(sale_items.product_id, 0) as product_id')
            ->selectRaw('MAX(sale_items.product_name_en) as product_name')
            ->selectRaw('COUNT(DISTINCT sales.id) as invoices')
            ->selectRaw("{$lineSum} as gross_sales")
            ->groupBy('product_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->product_id);

        $returnItems = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id');
        $this->applyDateRange($returnItems, 'sale_returns.return_date', $start, $end);
        $this->applyReturnChannelOnJoinedSales($returnItems, $channel);

        $returnSum = $this->lineTotalSumExpression('sale_return_items.return_amount');

        $returnRows = $returnItems
            ->selectRaw('COALESCE(sale_return_items.product_id, 0) as product_id')
            ->selectRaw("{$returnSum} as returns_total")
            ->groupBy('product_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->product_id);

        $ids = $saleRows->keys()->merge($returnRows->keys())->unique()->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values();
        $products = Product::query()->whereIn('id', $ids)->get()->keyBy('id');

        $keys = $saleRows->keys()->merge($returnRows->keys())->unique()->values();
        $rows = [];

        foreach ($keys as $key) {
            $productId = (int) $key;
            $product = $productId > 0 ? $products->get($productId) : null;
            $label = $product
                ? $this->productLabel($product)
                : (string) ($saleRows->get($key)?->product_name ?: __('reports.sales.unknown_product'));

            if (filled($search) && ! str_contains(mb_strtolower($label), mb_strtolower(trim($search)))) {
                continue;
            }

            $gross = $this->numeric($saleRows->get($key)?->gross_sales ?? 0);
            $invoices = (int) ($saleRows->get($key)?->invoices ?? 0);
            $refunds = $this->numeric($returnRows->get($key)?->returns_total ?? 0);

            $rows[] = $this->makeRow(
                id: (string) $productId,
                group: $label,
                invoices: $invoices,
                gross: $gross,
                returns: $refunds,
                net: $gross - $refunds,
                extras: ['product' => $label],
            );
        }

        usort($rows, fn (array $a, array $b): int => ($b['gross_sales_numeric'] <=> $a['gross_sales_numeric']));

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    private function makeRow(string $id, string $group, int $invoices, float $gross, float $returns, float $net, array $extras = []): array
    {
        $avg = $invoices > 0 ? $gross / $invoices : 0.0;

        return [
            'id' => $id,
            'group' => $group,
            'period' => $extras['period'] ?? $group,
            'channel' => $extras['channel'] ?? '—',
            'category' => $extras['category'] ?? '—',
            'customer' => $extras['customer'] ?? '—',
            'product' => $extras['product'] ?? '—',
            'invoices' => $invoices,
            'gross_sales' => $this->formatMoney($gross),
            'gross_sales_numeric' => $gross,
            'returns' => $this->formatMoney($returns),
            'returns_numeric' => $returns,
            'net_sales' => $this->formatMoney($net),
            'net_sales_numeric' => $net,
            'avg_ticket' => $this->formatMoney($avg),
            'avg_ticket_numeric' => $avg,
        ];
    }

    private function applyChannel(Builder $query, string $channel, string $saleIdColumn): void
    {
        if ($channel === 'online') {
            $query->whereExists(function ($inner) use ($saleIdColumn): void {
                $inner->select(DB::raw(1))
                    ->from('online_sale_orders')
                    ->whereColumn('online_sale_orders.sale_id', $saleIdColumn);
            });

            return;
        }

        if ($channel === 'pos') {
            $query->whereNotExists(function ($inner) use ($saleIdColumn): void {
                $inner->select(DB::raw(1))
                    ->from('online_sale_orders')
                    ->whereColumn('online_sale_orders.sale_id', $saleIdColumn);
            });
        }
    }

    /**
     * @param  Builder<SaleReturn>  $query
     */
    private function applyReturnChannel(Builder $query, string $channel): void
    {
        if ($channel === 'all') {
            return;
        }

        if ($channel === 'online') {
            $query->whereExists(function ($inner): void {
                $inner->select(DB::raw(1))
                    ->from('online_sale_orders')
                    ->whereColumn('online_sale_orders.sale_id', 'sale_returns.sale_id');
            });

            return;
        }

        $query->whereNotExists(function ($inner): void {
            $inner->select(DB::raw(1))
                ->from('online_sale_orders')
                ->whereColumn('online_sale_orders.sale_id', 'sale_returns.sale_id');
        });
    }

    private function applyReturnChannelOnJoinedSales(Builder $query, string $channel): void
    {
        if ($channel === 'all') {
            return;
        }

        if ($channel === 'online') {
            $query->whereExists(function ($inner): void {
                $inner->select(DB::raw(1))
                    ->from('online_sale_orders')
                    ->whereColumn('online_sale_orders.sale_id', 'sale_returns.sale_id');
            });

            return;
        }

        $query->whereNotExists(function ($inner): void {
            $inner->select(DB::raw(1))
                ->from('online_sale_orders')
                ->whereColumn('online_sale_orders.sale_id', 'sale_returns.sale_id');
        });
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

    private function lineTotalSumExpression(string $column): string
    {
        $cast = DB::connection()->getDriverName() === 'sqlite' ? 'REAL' : 'DECIMAL(14,2)';

        return "COALESCE(SUM(CAST({$column} AS {$cast})), 0)";
    }

    private function categoryLabel(?Category $category): string
    {
        if ($category === null) {
            return '';
        }

        if (app()->getLocale() === 'ur' && filled($category->name_ur)) {
            return (string) $category->name_ur;
        }

        return (string) ($category->name_en ?: $category->name_ur ?: '');
    }

    private function productLabel(Product $product): string
    {
        if (app()->getLocale() === 'ur' && filled($product->name_ur)) {
            return (string) $product->name_ur;
        }

        return (string) ($product->name_en ?: $product->name_ur ?: $product->sku ?: '');
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
}
