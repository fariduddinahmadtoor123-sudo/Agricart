<?php

namespace App\Services\Reports;

use App\Filament\Clusters\Catalog\ProductsPage;
use App\Models\Category;
use App\Models\Product;
use App\Services\Purchasing\ReorderService;
use Illuminate\Database\Eloquent\Builder;

class StockReportService
{
    public const INTERACTIVE_ROW_LIMIT = 500;

    public function __construct(
        private readonly ReorderService $reorder,
    ) {}

    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     kpis: array<string, int|float|string>,
     *     generated_at: string
     * }
     */
    public function build(?string $search = null, ?string $stockFilter = null, ?string $categoryFilter = null): array
    {
        $locale = app()->getLocale();
        $baseQuery = $this->query($search, $stockFilter, $categoryFilter);
        $products = (clone $baseQuery)->limit(self::INTERACTIVE_ROW_LIMIT)->get();
        $cast = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite' ? 'REAL' : 'DECIMAL(14,4)';
        $stock = "CAST(COALESCE(NULLIF(stock_qty, ''), '0') AS {$cast})";
        $alert = "CAST(COALESCE(NULLIF(alert_qty, ''), '0') AS {$cast})";
        $required = "CAST(COALESCE(NULLIF(required_qty, ''), '0') AS {$cast})";
        $totals = (clone $baseQuery)
            ->reorder()
            ->setEagerLoads([])
            ->selectRaw('COUNT(*) as total_skus')
            ->selectRaw("SUM(CASE WHEN {$stock} > 0 THEN 1 ELSE 0 END) as in_stock")
            ->selectRaw("SUM(CASE WHEN {$stock} > 0 AND {$alert} > 0 AND {$stock} <= {$alert} THEN 1 ELSE 0 END) as low_stock")
            ->selectRaw("SUM(CASE WHEN {$stock} <= 0 THEN 1 ELSE 0 END) as out_of_stock")
            ->selectRaw("SUM(CASE WHEN {$required} > 0 AND {$stock} < {$required} THEN 1 ELSE 0 END) as on_reorder")
            ->selectRaw("COALESCE(SUM({$stock}), 0) as total_on_hand")
            ->first();

        $rows = [];

        foreach ($products as $product) {
            $onHand = $this->numeric($product->stock_qty);
            $status = $this->statusFor($product, $onHand);
            $reserved = 0.0;
            $available = $onHand - $reserved;

            $rows[] = [
                'id' => $product->id,
                'product' => $this->productName($product, $locale),
                'sku' => (string) ($product->sku ?: $product->code ?: '—'),
                'category' => $this->categoryName($product, $locale),
                'on_hand' => $this->formatQty($onHand),
                'on_hand_numeric' => $onHand,
                'reserved' => $this->formatQty($reserved),
                'available' => $this->formatQty($available),
                'alert_qty' => filled($product->alert_qty) ? $this->formatQty($this->numeric($product->alert_qty)) : '—',
                'unit' => $this->unitName($product, $locale),
                'stock_status' => $status,
                'stock_status_label' => __('reports.stock.status.'.$status),
                'catalog_url' => $this->catalogUrl(),
            ];
        }

        return [
            'rows' => $rows,
            'kpis' => [
                'total_skus' => (int) ($totals?->total_skus ?? 0),
                'in_stock' => (int) ($totals?->in_stock ?? 0),
                'low_stock' => (int) ($totals?->low_stock ?? 0),
                'out_of_stock' => (int) ($totals?->out_of_stock ?? 0),
                'on_reorder' => (int) ($totals?->on_reorder ?? 0),
                'total_on_hand' => $this->formatQty((float) ($totals?->total_on_hand ?? 0)),
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
            'product' => __('reports.columns.product'),
            'sku' => __('reports.columns.sku'),
            'category' => __('reports.columns.category'),
            'on_hand' => __('reports.columns.on_hand'),
            'reserved' => __('reports.columns.reserved'),
            'available' => __('reports.columns.available'),
            'alert_qty' => __('reports.columns.alert_qty'),
            'unit' => __('reports.columns.unit'),
            'stock_status' => __('reports.columns.stock_status'),
        ];

        $columns = array_values(array_filter(
            $visibleColumns,
            fn (string $col): bool => isset($labels[$col]),
        ));

        if ($columns === []) {
            $columns = ['product', 'sku', 'category', 'on_hand', 'available', 'stock_status'];
        }

        $header = array_map(fn (string $col): string => $labels[$col], $columns);
        $matrix = [$header];

        foreach ($rows as $row) {
            $line = [];

            foreach ($columns as $col) {
                $line[] = $col === 'stock_status'
                    ? (string) ($row['stock_status_label'] ?? $row['stock_status'] ?? '')
                    : (string) ($row[$col] ?? '');
            }

            $matrix[] = $line;
        }

        return $matrix;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function categoryFilterOptions(): array
    {
        $locale = app()->getLocale();
        $options = [
            ['label' => __('reports.filters.category_all'), 'value' => 'category:all'],
        ];

        $categories = Category::query()
            ->where('status', 'active')
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ur']);

        foreach ($categories as $category) {
            $label = $locale === 'ur' && filled($category->name_ur)
                ? (string) $category->name_ur
                : (string) $category->name_en;

            $options[] = [
                'label' => $label,
                'value' => 'category:'.$category->id,
            ];
        }

        return $options;
    }

    /**
     * @return Builder<Product>
     */
    private function query(?string $search, ?string $stockFilter, ?string $categoryFilter): Builder
    {
        $query = Product::query()
            ->with(['category', 'unit'])
            ->where('status', 'active')
            ->orderBy('name_en');

        if (filled($search)) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('name_en', 'like', $term)
                    ->orWhere('name_ur', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('code', 'like', $term);
            });
        }

        if (filled($categoryFilter) && $categoryFilter !== 'all' && ctype_digit((string) $categoryFilter)) {
            $query->where('category_id', (int) $categoryFilter);
        }

        $stock = $stockFilter ?: 'all';

        if ($stock === 'out') {
            $query->where(function (Builder $inner): void {
                $inner->whereNull('stock_qty')
                    ->orWhere('stock_qty', '<=', 0);
            });
        } elseif ($stock === 'in') {
            $query->where('stock_qty', '>', 0);
        } elseif ($stock === 'low') {
            $query->where('stock_qty', '>', 0)
                ->whereNotNull('alert_qty')
                ->where('alert_qty', '!=', '')
                ->whereRaw('CAST(COALESCE(stock_qty, 0) AS REAL) <= CAST(alert_qty AS REAL)');
        } elseif ($stock === 'reorder') {
            $query->whereNotNull('required_qty')
                ->where('required_qty', '!=', '')
                ->whereRaw('CAST(COALESCE(stock_qty, 0) AS REAL) < CAST(required_qty AS REAL)');
        }

        return $query;
    }

    private function statusFor(Product $product, float $onHand): string
    {
        if ($onHand <= 0) {
            return 'out';
        }

        $alert = $this->numeric($product->alert_qty);

        if ($alert > 0 && $onHand <= $alert) {
            return 'low';
        }

        return 'in';
    }

    private function productName(Product $product, string $locale): string
    {
        if ($locale === 'ur' && filled($product->name_ur)) {
            return (string) $product->name_ur;
        }

        return (string) ($product->name_en ?: '—');
    }

    private function categoryName(Product $product, string $locale): string
    {
        $category = $product->category;

        if ($category === null) {
            return '—';
        }

        if ($locale === 'ur' && filled($category->name_ur)) {
            return (string) $category->name_ur;
        }

        return (string) ($category->name_en ?: '—');
    }

    private function unitName(Product $product, string $locale): string
    {
        $unit = $product->unit;

        if ($unit === null) {
            return '—';
        }

        if ($locale === 'ur' && filled($unit->name_ur ?? null)) {
            return (string) $unit->name_ur;
        }

        return (string) ($unit->name_en ?? '—');
    }

    private function numeric(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function formatQty(float $value): string
    {
        if (abs($value - round($value)) < 0.00001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function catalogUrl(): string
    {
        try {
            return ProductsPage::getUrl();
        } catch (\Throwable) {
            return '/admin/catalog/products';
        }
    }
}
