<?php

namespace App\Services\Store;

use App\Models\Product;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Sales\SaleService;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class StoreCartService
{
    private const SESSION_KEY = 'agricart_store_cart';

    public function __construct(
        private readonly QuantityPricingEngine $quantityPricing,
        private readonly SaleService $saleService,
        private readonly ChannelPricingEngine $priceEngine,
    ) {}

    public function totalQuantity(): int
    {
        $sum = 0;

        foreach ($this->rawItems() as $item) {
            $sum += (int) max(0, floor($this->saleService->numeric((string) ($item['quantity'] ?? '0'))));
        }

        return $sum;
    }

    public function isEmpty(): bool
    {
        return $this->rawItems() === [];
    }

    public function add(int $productId, float $quantity): void
    {
        $quantity = max(1, $quantity);
        $product = $this->findActiveProduct($productId);

        $items = $this->rawItems();
        $found = false;

        foreach ($items as &$item) {
            if ((int) $item['product_id'] === $productId) {
                $item['quantity'] = $this->saleService->formatQty(
                    $this->saleService->numeric((string) $item['quantity']) + $quantity,
                );
                $found = true;
                break;
            }
        }
        unset($item);

        if (! $found) {
            $items[] = [
                'product_id' => $productId,
                'quantity' => $this->saleService->formatQty($quantity),
            ];
        }

        $this->persist($items);
        $this->assertStockForProduct($product, $this->lineQuantity($productId));
    }

    public function setQuantity(int $productId, float $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($productId);

            return;
        }

        $product = $this->findActiveProduct($productId);
        $items = $this->rawItems();
        $updated = false;

        foreach ($items as &$item) {
            if ((int) $item['product_id'] === $productId) {
                $item['quantity'] = $this->saleService->formatQty($quantity);
                $updated = true;
                break;
            }
        }
        unset($item);

        if (! $updated) {
            throw ValidationException::withMessages([
                'cart' => [__('agricart.storefront.cart.item_not_found')],
            ]);
        }

        $this->persist($items);
        $this->assertStockForProduct($product, $quantity);
    }

    public function remove(int $productId): void
    {
        $items = array_values(array_filter(
            $this->rawItems(),
            fn (array $item): bool => (int) $item['product_id'] !== $productId,
        ));

        $this->persist($items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * @return list<array{
     *     product: Product,
     *     quantity: string,
     *     quantity_numeric: float,
     *     unit_price: string,
     *     unit_price_formatted: string,
     *     line_total: string,
     *     line_total_formatted: string,
     *     price_tier_key: string,
     * }>
     */
    public function lines(): array
    {
        $ids = collect($this->rawItems())->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return [];
        }

        $products = Product::query()
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->with(['brand', 'unit', 'packUnit'])
            ->get()
            ->keyBy('id');

        $lines = [];

        foreach ($this->rawItems() as $item) {
            $productId = (int) $item['product_id'];
            $product = $products->get($productId);

            if ($product === null) {
                continue;
            }

            $qty = $this->saleService->numeric((string) ($item['quantity'] ?? '0'));

            if ($qty <= 0) {
                continue;
            }

            $resolved = $this->quantityPricing->resolveForQuantity($product, $qty, forPos: false);
            $unitPrice = $resolved['price'] ?? $product->retail_price ?? '0';
            $tierKey = $resolved['tier']->tier_key ?? 'retail';
            $unitNumeric = $this->saleService->numeric((string) $unitPrice);
            $lineTotal = $unitNumeric * $qty;

            $lines[] = [
                'product' => $product,
                'quantity' => $this->saleService->formatQty($qty),
                'quantity_numeric' => $qty,
                'unit_price' => $this->saleService->formatMoney($unitNumeric),
                'unit_price_formatted' => $this->priceEngine->formatPrice($unitNumeric),
                'line_total' => $this->saleService->formatMoney($lineTotal),
                'line_total_formatted' => $this->priceEngine->formatPrice($lineTotal),
                'price_tier_key' => $tierKey,
            ];
        }

        return $lines;
    }

    public function subtotalNumeric(): float
    {
        $sum = 0.0;

        foreach ($this->lines() as $line) {
            $sum += $this->saleService->numeric($line['line_total']);
        }

        return $sum;
    }

    public function formattedSubtotal(): string
    {
        return $this->priceEngine->formatPrice($this->subtotalNumeric());
    }

    /**
     * @return list<array{product_id: int, quantity: string, unit_price: string, price_tier_key: string}>
     */
    public function checkoutRows(): array
    {
        $rows = [];

        foreach ($this->lines() as $line) {
            $rows[] = [
                'product_id' => $line['product']->id,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'price_tier_key' => $line['price_tier_key'],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{product_id: int, quantity: string}>
     */
    private function rawItems(): array
    {
        $items = Session::get(self::SESSION_KEY, []);

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['product_id'])) {
                continue;
            }

            $normalized[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => (string) ($item['quantity'] ?? '1'),
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array{product_id: int, quantity: string}>  $items
     */
    private function persist(array $items): void
    {
        Session::put(self::SESSION_KEY, $items);
    }

    private function findActiveProduct(int $productId): Product
    {
        $product = Product::query()
            ->where('id', $productId)
            ->where('status', 'active')
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product' => [__('agricart.storefront.cart.product_unavailable')],
            ]);
        }

        return $product;
    }

    private function lineQuantity(int $productId): float
    {
        foreach ($this->rawItems() as $item) {
            if ((int) $item['product_id'] === $productId) {
                return $this->saleService->numeric((string) $item['quantity']);
            }
        }

        return 0.0;
    }

    private function assertStockForProduct(Product $product, float $quantity): void
    {
        if ($this->saleService->allowsOverselling()) {
            return;
        }

        if ($product->stock_qty === null) {
            return;
        }

        $stock = (float) $product->stock_qty;

        if ($quantity > $stock) {
            throw ValidationException::withMessages([
                'quantity' => [__('agricart.storefront.cart.insufficient_stock', ['available' => $this->saleService->formatQty($stock)])],
            ]);
        }
    }
}
