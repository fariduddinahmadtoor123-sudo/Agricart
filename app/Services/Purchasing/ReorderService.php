<?php

namespace App\Services\Purchasing;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\ReorderQueueBatch;
use App\Models\ReorderQueueItem;
use App\Services\PricingControls\SecretCodingEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderService
{
    public const STALE_DAYS = 7;

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
        $pendingProductIds = $this->pendingQueueProductIds();

        return Product::query()
            ->where('status', 'active')
            ->whereNotNull('required_qty')
            ->where('required_qty', '!=', '')
            ->orderBy('name_en')
            ->get()
            ->filter(fn (Product $product): bool => $this->needsReorder($product))
            ->map(function (Product $product) use ($locale, $pendingProductIds): array {
                $inQueue = in_array($product->id, $pendingProductIds, true);
                $sendCount = $this->productQueueSendCount($product->id);

                return [
                    'product_id' => $product->id,
                    'product' => $product,
                    'name' => $this->productDisplayName($product, $locale),
                    'image_path' => $product->image_path,
                    'stock_qty' => $this->numericStock($product),
                    'required_qty' => $this->orderQty($product),
                    'previous_price' => $this->purchaseService->previousPurchasePrice($product->id),
                    'in_queue' => $inQueue,
                    'send_count' => $sendCount,
                ];
            })
            ->filter(function (array $row) use ($filter): bool {
                return match ($filter) {
                    'in_queue' => $row['in_queue'],
                    'available' => ! $row['in_queue'],
                    'repeated' => $row['send_count'] > 0 && ! $row['in_queue'],
                    default => true,
                };
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

        return DB::transaction(function () use ($toQueue, $notes): ReorderQueueBatch {
            $batch = ReorderQueueBatch::query()->create([
                'batch_number' => $this->nextBatchNumber(),
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($toQueue as $productId) {
                $product = Product::query()->find($productId);

                if ($product === null || ! $this->needsReorder($product)) {
                    continue;
                }

                $sendCount = $this->productQueueSendCount($productId) + 1;

                ReorderQueueItem::query()->create([
                    'reorder_queue_batch_id' => $batch->id,
                    'product_id' => $product->id,
                    'required_qty' => $this->orderQty($product),
                    'previous_price' => $this->purchaseService->previousPurchasePrice($product->id),
                    'status' => ReorderQueueItem::STATUS_PENDING,
                    'send_count' => $sendCount,
                    'queued_at' => now(),
                ]);
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

            $qty = $this->numericValue($item->purchase_qty);

            if ($qty <= 0) {
                continue;
            }

            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if ($product === null) {
                continue;
            }

            $product->update([
                'stock_qty' => round($this->numericStock($product) + $qty, 2),
            ]);

            $fulfilledProductIds[] = $product->id;
        }

        $this->fulfillQueueForProducts(array_values(array_unique($fulfilledProductIds)));
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
        $next = (int) ReorderQueueBatch::query()->max('id') + 1;

        do {
            $number = 'ROQ-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (ReorderQueueBatch::query()->where('batch_number', $number)->exists());

        return $number;
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
        return max(0, $this->numericValue($product->stock_qty));
    }

    private function numericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }
}
