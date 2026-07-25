<?php

namespace App\Services\Purchasing;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\ReorderQueueBatch;
use App\Models\ReorderQueueItem;
use App\Models\Sale;
use App\Services\PricingControls\SecretCodingEngine;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderService
{
    public const STALE_DAYS = 7;
    public const CANDIDATE_LIMIT = 500;

    public function __construct(
        private readonly PurchaseService $purchaseService,
        private readonly PriceTagService $priceTags,
        private readonly SecretCodingEngine $secretCoding,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function reorderCandidates(string $locale = 'en', string $filter = 'available'): Collection
    {
        $queueStats = ReorderQueueItem::query()
            ->select('product_id')
            ->selectRaw('MAX(send_count) as send_count')
            ->selectRaw("MAX(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_queue", [ReorderQueueItem::STATUS_PENDING])
            ->groupBy('product_id');

        $products = Product::query()
            ->leftJoinSub($queueStats, 'queue_stats', 'queue_stats.product_id', '=', 'products.id')
            ->select('products.*')
            ->selectRaw('COALESCE(queue_stats.send_count, 0) as queue_send_count')
            ->selectRaw('COALESCE(queue_stats.in_queue, 0) as queue_in_queue')
            ->where('status', 'active')
            ->whereNotNull('required_qty')
            ->where('required_qty', '!=', '')
            ->whereRaw("CAST(COALESCE(NULLIF(products.stock_qty, ''), '0') AS DECIMAL(14,4)) < CAST(products.required_qty AS DECIMAL(14,4))")
            ->when($filter === 'in_queue', fn ($query) => $query->where('queue_stats.in_queue', 1))
            ->when($filter === 'available', fn ($query) => $query->where(fn ($inner) => $inner->whereNull('queue_stats.in_queue')->orWhere('queue_stats.in_queue', 0)))
            ->when($filter === 'repeated', fn ($query) => $query
                ->where('queue_stats.send_count', '>', 0)
                ->where(fn ($inner) => $inner->whereNull('queue_stats.in_queue')->orWhere('queue_stats.in_queue', 0)))
            ->orderBy('products.name_en')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        $previousPrices = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereIn('purchase_items.product_id', $products->pluck('id'))
            ->whereNotNull('purchase_items.current_rate')
            ->where('purchase_items.current_rate', '!=', '')
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchases.id')
            ->orderByDesc('purchase_items.id')
            ->get(['purchase_items.product_id', 'purchase_items.current_rate'])
            ->unique('product_id')
            ->pluck('current_rate', 'product_id');

        return $products
            ->map(function (Product $product) use ($locale, $previousPrices): array {
                $inQueue = (bool) $product->getAttribute('queue_in_queue');
                $sendCount = (int) $product->getAttribute('queue_send_count');

                return [
                    'product_id' => $product->id,
                    'product' => $product,
                    'name' => $this->productDisplayName($product, $locale),
                    'image_path' => $product->image_path,
                    'stock_qty' => $this->numericStock($product),
                    'required_qty' => $this->orderQty($product),
                    'previous_price' => (string) ($previousPrices[$product->id] ?? ''),
                    'in_queue' => $inQueue,
                    'send_count' => $sendCount,
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, ReorderQueueItem>
     */
    public function queueItems(string $filter = 'pending'): Collection
    {
        $query = ReorderQueueItem::query()
            ->with(['product', 'batch'])
            ->where('status', ReorderQueueItem::STATUS_PENDING)
            ->orderByDesc('queued_at');

        if ($filter === 'stale') {
            $query->where('queued_at', '<=', now()->subDays(self::STALE_DAYS));
        }

        return $query->get();
    }

    /**
     * @param  list<int>  $productIds
     */
    public function sendToQueue(array $productIds, ?string $notes = null): ?ReorderQueueBatch
    {
        $productIds = array_values(array_unique(array_filter($productIds)));

        if ($productIds === []) {
            return null;
        }

        $pendingIds = $this->pendingQueueProductIds();
        $toQueue = array_values(array_diff($productIds, $pendingIds));

        if ($toQueue === []) {
            return null;
        }

        return DB::transaction(function () use ($toQueue, $notes): ?ReorderQueueBatch {
            $batch = ReorderQueueBatch::query()->create([
                'batch_number' => $this->nextBatchNumber(),
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);
            $inserted = 0;

            foreach ($toQueue as $productId) {
                $product = Product::query()->find($productId);

                if ($product === null || ! $this->needsReorder($product)) {
                    continue;
                }

                $sendCount = $this->productQueueSendCount($productId) + 1;

                $inserted += ReorderQueueItem::query()->insertOrIgnore([
                    'reorder_queue_batch_id' => $batch->id,
                    'product_id' => $product->id,
                    'required_qty' => $this->orderQty($product),
                    'previous_price' => $this->purchaseService->previousPurchasePrice($product->id),
                    'status' => ReorderQueueItem::STATUS_PENDING,
                    'active_key' => 'product:'.$product->id,
                    'send_count' => $sendCount,
                    'queued_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($inserted === 0) {
                $batch->delete();

                return null;
            }

            return $batch->fresh(['items.product']);
        });
    }

    /**
     * @param  list<int>  $productIds
     */
    public function fulfillQueueForProducts(array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        ReorderQueueItem::query()
            ->whereIn('product_id', $productIds)
            ->where('status', ReorderQueueItem::STATUS_PENDING)
            ->update([
                'status' => ReorderQueueItem::STATUS_FULFILLED,
                'fulfilled_at' => now(),
                'active_key' => null,
            ]);
    }

    public function applyPurchaseStock(Purchase $purchase): void
    {
        if (! in_array($purchase->delivery_status, [Purchase::DELIVERY_RECEIVED, Purchase::DELIVERY_PARTIAL], true)) {
            return;
        }

        $purchase->loadMissing('items');
        $fulfilledProductIds = [];

        foreach ($purchase->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $qty = $this->stockCreditQuantity($purchase, $item);

            if ($qty <= 0) {
                continue;
            }

            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if ($product === null) {
                continue;
            }

            $product->update([
                'stock_qty' => round($this->rawStock($product) + $qty, 2),
            ]);

            $fulfilledProductIds[] = $product->id;
        }

        $this->fulfillQueueForProducts(array_values(array_unique($fulfilledProductIds)));
    }

    public function reversePurchaseStock(Purchase $purchase): void
    {
        if (! in_array($purchase->delivery_status, [Purchase::DELIVERY_RECEIVED, Purchase::DELIVERY_PARTIAL], true)) {
            return;
        }

        $purchase->loadMissing('items');

        foreach ($purchase->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $qty = $this->stockCreditQuantity($purchase, $item);

            if ($qty <= 0) {
                continue;
            }

            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if ($product === null) {
                continue;
            }

            $product->update([
                'stock_qty' => round($this->rawStock($product) - $qty, 2),
            ]);
        }
    }

    /**
     * Quantity that should hit on-hand stock for this purchase line.
     * Received → full purchase_qty; partial → received_qty only; pending → 0.
     */
    public function stockCreditQuantity(Purchase $purchase, PurchaseItem|\stdClass|array $item): float
    {
        if ($purchase->delivery_status === Purchase::DELIVERY_PENDING) {
            return 0.0;
        }

        $purchaseQty = $this->numericValue(is_array($item) ? ($item['purchase_qty'] ?? null) : ($item->purchase_qty ?? null));
        $receivedQty = $this->numericValue(is_array($item) ? ($item['received_qty'] ?? null) : ($item->received_qty ?? null));

        if ($purchase->delivery_status === Purchase::DELIVERY_RECEIVED) {
            return max(0.0, $purchaseQty);
        }

        if ($purchase->delivery_status === Purchase::DELIVERY_PARTIAL) {
            return max(0.0, min($receivedQty, $purchaseQty > 0 ? $purchaseQty : $receivedQty));
        }

        return 0.0;
    }

    public function refreshPurchaseStock(Purchase $before, Purchase $after): void
    {
        $before->loadMissing('items');
        $after->loadMissing('items');

        $this->reversePurchaseStock($before);
        $this->applyPurchaseStock($after);
    }

    /**
     * Rebuild on-hand stock from purchases, completed sales, and returns.
     *
     * @return array{product_id: int, stock_qty: float}
     */
    public function rebuildProductStock(Product $product): array
    {
        $productId = (int) $product->id;

        $cast = DB::connection()->getDriverName() === 'sqlite' ? 'REAL' : 'DECIMAL(14,4)';
        $creditExpression = "CASE
            WHEN purchases.delivery_status = '".Purchase::DELIVERY_RECEIVED."' THEN CAST(COALESCE(purchase_items.purchase_qty, 0) AS {$cast})
            WHEN purchases.delivery_status = '".Purchase::DELIVERY_PARTIAL."' THEN CAST(COALESCE(purchase_items.received_qty, 0) AS {$cast})
            ELSE 0
        END";

        $purchased = (float) (DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchase_items.product_id', $productId)
            ->whereIn('purchases.delivery_status', [Purchase::DELIVERY_RECEIVED, Purchase::DELIVERY_PARTIAL])
            ->selectRaw("COALESCE(SUM({$creditExpression}), 0) as total")
            ->value('total') ?? 0);

        $sold = (float) (DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.product_id', $productId)
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->selectRaw($this->castSumExpression('sale_items.quantity').' as total')
            ->value('total') ?? 0);

        $returned = (float) (DB::table('sale_return_items')
            ->where('sale_return_items.product_id', $productId)
            ->selectRaw($this->castSumExpression('sale_return_items.return_quantity').' as total')
            ->value('total') ?? 0);

        $stock = round($purchased - $sold + $returned, 2);

        $product->update(['stock_qty' => $stock]);

        return [
            'product_id' => $productId,
            'stock_qty' => $stock,
        ];
    }

    /**
     * @return list<array{product_id: int, stock_qty: float}>
     */
    public function rebuildAllProductStock(): array
    {
        $results = [];

        Product::query()->orderBy('id')->each(function (Product $product) use (&$results): void {
            $results[] = $this->rebuildProductStock($product);
        });

        return $results;
    }

    private function castSumExpression(string $column): string
    {
        $cast = DB::connection()->getDriverName() === 'sqlite' ? 'REAL' : 'DECIMAL(14,4)';

        return "COALESCE(SUM(CAST({$column} AS {$cast})), 0)";
    }

    public function needsReorder(Product $product): bool
    {
        $required = $this->numericValue($product->required_qty);

        if ($required <= 0) {
            return false;
        }

        return $this->numericStock($product) < $required;
    }

    public function orderQty(Product $product): string
    {
        $required = $this->numericValue($product->required_qty);
        $shortage = max(0, $required - $this->numericStock($product));

        return $shortage > 0 ? rtrim(rtrim(number_format($shortage, 2, '.', ''), '0'), '.') : '';
    }

    public function nextBatchNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'reorder_queue_batches.batch_number',
            'ROQ-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('reorder_queue_batches', 'batch_number', 'ROQ-'),
        );
    }

    /**
     * @return list<int>
     */
    public function pendingQueueProductIds(): array
    {
        return ReorderQueueItem::query()
            ->where('status', ReorderQueueItem::STATUS_PENDING)
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();
    }

    public function productQueueSendCount(int $productId): int
    {
        return (int) ReorderQueueItem::query()
            ->where('product_id', $productId)
            ->max('send_count');
    }

    public function productDisplayName(Product $product, string $locale): string
    {
        $locale = in_array($locale, ['en', 'ur'], true) ? $locale : 'en';

        if ($locale === 'ur' && filled($product->name_ur)) {
            return (string) $product->name_ur;
        }

        return (string) $product->name_en;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rowsForPrint(Collection $items, string $locale = 'en'): array
    {
        return $items->map(function ($item, int $index) use ($locale): array {
            if ($item instanceof ReorderQueueItem) {
                $product = $item->product;

                return [
                    'sr' => $index + 1,
                    'name' => $product ? $this->productDisplayName($product, $locale) : '—',
                    'image_url' => $product?->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) : null,
                    'required_qty' => (string) ($item->required_qty ?? ''),
                    'previous_price' => (string) ($item->previous_price ?? ''),
                ];
            }

            return [
                'sr' => $index + 1,
                'name' => (string) ($item['name'] ?? ''),
                'image_url' => filled($item['image_path'] ?? null)
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url((string) $item['image_path'])
                    : null,
                'required_qty' => (string) ($item['required_qty'] ?? ''),
                'previous_price' => (string) ($item['previous_price'] ?? ''),
            ];
        })->all();
    }

    /**
     * @param  iterable<mixed>  $rows
     * @return array{
     *     line_count: int,
     *     qty_total: float,
     *     total: float,
     *     formatted: string,
     *     encoded: string
     * }
     */
    public function summarizeOrderValue(iterable $rows): array
    {
        $total = 0.0;
        $lineCount = 0;
        $qtyTotal = 0.0;

        foreach ($rows as $row) {
            [$qty, $price] = $this->rowQtyAndPrice($row);

            if ($qty <= 0) {
                continue;
            }

            $lineCount++;
            $qtyTotal += $qty;

            if ($price > 0) {
                $total += $qty * $price;
            }
        }

        $formatted = $lineCount > 0 && $total > 0
            ? $this->priceTags->formatPrice($total)
            : '';

        return [
            'line_count' => $lineCount,
            'qty_total' => $qtyTotal,
            'total' => $total,
            'formatted' => $formatted,
            'encoded' => $formatted !== '' ? $this->secretCoding->encodePrice($formatted) : '',
        ];
    }

    public function formatQuantity(float $qty): string
    {
        if ($qty <= 0) {
            return '0';
        }

        return rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function rowQtyAndPrice(mixed $row): array
    {
        if ($row instanceof ReorderQueueItem) {
            return [
                $this->numericValue($row->required_qty),
                $this->numericValue($row->previous_price),
            ];
        }

        if (is_array($row)) {
            return [
                $this->numericValue($row['required_qty'] ?? null),
                $this->numericValue($row['previous_price'] ?? null),
            ];
        }

        return [0.0, 0.0];
    }

    private function numericStock(Product $product): float
    {
        return max(0, $this->rawStock($product));
    }

    /**
     * On-hand for inventory mutations — keep negative balances so a later
     * purchase does not erase prior oversell quantity.
     */
    private function rawStock(Product $product): float
    {
        return $this->numericValue($product->stock_qty);
    }

    private function numericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }
}
