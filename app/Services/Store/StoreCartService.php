<?php

namespace App\Services\Store;

use App\Models\CustomerStoreCart;
use App\Models\Product;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Sales\SaleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class StoreCartService
{
    public const SESSION_KEY = 'agricart_store_cart';

    public function __construct(
        private readonly QuantityPricingEngine $quantityPricing,
        private readonly SaleService $saleService,
        private readonly ChannelPricingEngine $priceEngine,
    ) {}

    public function totalQuantity(): int
    {
        $sum = 0;

        foreach ($this->lines() as $line) {
            $sum += (int) max(0, floor($line['quantity_numeric']));
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

        $proposedQuantity = $this->quantityFromItems($items, $productId);
        $this->assertPurchasable($product);
        $this->assertStockForProduct($product, $proposedQuantity);
        $this->persist($items);
    }

    /**
     * Add multiple package lines with optional locked unit prices (after package discount).
     *
     * @param  list<array{product_id: int, quantity: float|string, unit_price?: float|string|null}>  $lines
     */
    public function addLines(array $lines): void
    {
        $items = $this->rawItems();

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = max(0, (float) ($line['quantity'] ?? 0));

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $product = $this->findActiveProduct($productId);
            $this->assertPurchasable($product);

            $unitPrice = array_key_exists('unit_price', $line) && $line['unit_price'] !== null && $line['unit_price'] !== ''
                ? $this->saleService->formatMoney($this->saleService->numeric((string) $line['unit_price']))
                : null;

            $found = false;

            foreach ($items as &$item) {
                if ((int) $item['product_id'] === $productId) {
                    $item['quantity'] = $this->saleService->formatQty(
                        $this->saleService->numeric((string) $item['quantity']) + $quantity,
                    );
                    if ($unitPrice !== null) {
                        $item['unit_price'] = $unitPrice;
                    }
                    $found = true;
                    break;
                }
            }
            unset($item);

            if (! $found) {
                $row = [
                    'product_id' => $productId,
                    'quantity' => $this->saleService->formatQty($quantity),
                ];
                if ($unitPrice !== null) {
                    $row['unit_price'] = $unitPrice;
                }
                $items[] = $row;
            }

            $this->assertStockForProduct($product, $this->quantityFromItems($items, $productId));
        }

        $this->persist($items);
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

        $this->assertPurchasable($product);
        $this->assertStockForProduct($product, $quantity);
        $this->persist($items);
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

        $customerId = $this->authenticatedCustomerId();

        if ($customerId !== null) {
            CustomerStoreCart::query()->where('customer_id', $customerId)->delete();
        }
    }

    /**
     * Persist the current session cart to the customer account (e.g. before logout).
     */
    public function persistForAuthenticatedCustomer(): void
    {
        $customerId = $this->authenticatedCustomerId();

        if ($customerId === null) {
            return;
        }

        $this->writeCustomerCart($customerId, $this->rawItems());
    }

    /**
     * Merge session cart with saved cart after login / registration.
     *
     * @param  list<array{product_id: int, quantity: string}>  $sessionItemsBeforeLogin
     */
    public function mergeAfterCustomerLogin(int $customerId, array $sessionItemsBeforeLogin = []): void
    {
        $saved = $this->readCustomerCart($customerId);
        $merged = $this->mergeItemLists($saved, $this->normalizeRawItems($sessionItemsBeforeLogin));
        [$merged, $adjusted] = $this->clampItemsToActiveStock($merged);

        if ($merged === []) {
            Session::forget(self::SESSION_KEY);
            CustomerStoreCart::query()->where('customer_id', $customerId)->delete();

            if ($adjusted) {
                session()->flash('store_status', __('agricart.storefront.cart.merge_adjusted'));
                session()->flash('store_status_type', 'warning');
            }

            return;
        }

        Session::put(self::SESSION_KEY, $merged);
        $this->writeCustomerCart($customerId, $merged);

        if ($adjusted) {
            session()->flash('store_status', __('agricart.storefront.cart.merge_adjusted'));
            session()->flash('store_status_type', 'warning');
        }
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
        $this->pruneInactiveFromSession();

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
            $unitPrice = $resolved['price'] ?? $product->wholesale_price ?? $product->retail_price ?? '0';
            $tierKey = $resolved['tier']->tier_key ?? 'wholesale';

            if (isset($item['unit_price']) && is_numeric($item['unit_price'])) {
                $unitPrice = (string) $item['unit_price'];
            }

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
            $items = [];
        }

        $normalized = $this->normalizeRawItems($items);

        if ($normalized !== []) {
            return $normalized;
        }

        $customerId = $this->authenticatedCustomerId();

        if ($customerId === null) {
            return [];
        }

        $saved = $this->readCustomerCart($customerId);

        if ($saved === []) {
            return [];
        }

        Session::put(self::SESSION_KEY, $saved);

        return $saved;
    }

    /**
     * @param  list<array{product_id?: mixed, quantity?: mixed, unit_price?: mixed}>  $items
     * @return list<array{product_id: int, quantity: string, unit_price?: string}>
     */
    private function normalizeRawItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['product_id'])) {
                continue;
            }

            $row = [
                'product_id' => (int) $item['product_id'],
                'quantity' => (string) ($item['quantity'] ?? '1'),
            ];

            if (isset($item['unit_price']) && is_numeric($item['unit_price'])) {
                $row['unit_price'] = $this->saleService->formatMoney(
                    $this->saleService->numeric((string) $item['unit_price']),
                );
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * @param  list<array{product_id: int, quantity: string}>  $items
     */
    private function persist(array $items): void
    {
        Session::put(self::SESSION_KEY, $items);

        $customerId = $this->authenticatedCustomerId();

        if ($customerId !== null) {
            $this->writeCustomerCart($customerId, $items);
        }
    }

    private function authenticatedCustomerId(): ?int
    {
        $id = Auth::guard('customer')->id();

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * @return list<array{product_id: int, quantity: string}>
     */
    private function readCustomerCart(int $customerId): array
    {
        $row = CustomerStoreCart::query()->where('customer_id', $customerId)->first();

        if ($row === null || ! is_array($row->items)) {
            return [];
        }

        return $this->normalizeRawItems($row->items);
    }

    /**
     * @param  list<array{product_id: int, quantity: string}>  $items
     */
    private function writeCustomerCart(int $customerId, array $items): void
    {
        if ($items === []) {
            CustomerStoreCart::query()->where('customer_id', $customerId)->delete();

            return;
        }

        CustomerStoreCart::query()->updateOrCreate(
            ['customer_id' => $customerId],
            ['items' => $items],
        );
    }

    /**
     * @param  list<array{product_id: int, quantity: string}>  $base
     * @param  list<array{product_id: int, quantity: string}>  $incoming
     * @return list<array{product_id: int, quantity: string}>
     */
    private function mergeItemLists(array $base, array $incoming): array
    {
        $byProduct = [];

        foreach (array_merge($base, $incoming) as $item) {
            $productId = (int) $item['product_id'];
            $qty = $this->saleService->numeric((string) $item['quantity']);

            if ($qty <= 0) {
                continue;
            }

            if (isset($byProduct[$productId])) {
                $qty += $this->saleService->numeric((string) $byProduct[$productId]['quantity']);
            }

            $row = [
                'product_id' => $productId,
                'quantity' => $this->saleService->formatQty($qty),
            ];

            $incomingPrice = isset($item['unit_price']) && is_numeric($item['unit_price'])
                ? $this->saleService->formatMoney($this->saleService->numeric((string) $item['unit_price']))
                : null;
            $existingPrice = $byProduct[$productId]['unit_price'] ?? null;

            if ($incomingPrice !== null && $existingPrice !== null) {
                $row['unit_price'] = $this->saleService->numeric($incomingPrice) < $this->saleService->numeric($existingPrice)
                    ? $incomingPrice
                    : $existingPrice;
            } elseif ($incomingPrice !== null) {
                $row['unit_price'] = $incomingPrice;
            } elseif ($existingPrice !== null) {
                $row['unit_price'] = $existingPrice;
            }

            $byProduct[$productId] = $row;
        }

        return array_values($byProduct);
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
        return $this->quantityFromItems($this->rawItems(), $productId);
    }

    /**
     * @param  list<array{product_id: int, quantity: string}>  $items
     */
    private function quantityFromItems(array $items, int $productId): float
    {
        foreach ($items as $item) {
            if ((int) $item['product_id'] === $productId) {
                return $this->saleService->numeric((string) $item['quantity']);
            }
        }

        return 0.0;
    }

    private function assertPurchasable(Product $product): void
    {
        if (! $product->isPriced()) {
            throw ValidationException::withMessages([
                'product' => [__('agricart.storefront.cart.price_pending')],
            ]);
        }
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

    private function pruneInactiveFromSession(): void
    {
        $items = Session::get(self::SESSION_KEY);

        if (! is_array($items) || $items === []) {
            return;
        }

        $normalized = $this->normalizeRawItems($items);
        $ids = collect($normalized)->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return;
        }

        $activeIds = Product::query()
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $activeSet = array_flip($activeIds);

        $pruned = array_values(array_filter(
            $normalized,
            static fn (array $item): bool => isset($activeSet[(int) $item['product_id']]),
        ));

        if ($pruned === $normalized) {
            return;
        }

        if ($pruned === []) {
            Session::forget(self::SESSION_KEY);

            $customerId = $this->authenticatedCustomerId();

            if ($customerId !== null) {
                CustomerStoreCart::query()->where('customer_id', $customerId)->delete();
            }

            return;
        }

        $this->persist($pruned);
    }

    /**
     * @param  list<array{product_id: int, quantity: string}>  $items
     * @return array{0: list<array{product_id: int, quantity: string}>, 1: bool}
     */
    private function clampItemsToActiveStock(array $items): array
    {
        if ($items === []) {
            return [[], false];
        }

        $ids = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        $products = Product::query()
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $adjusted = false;
        $result = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $product = $products->get($productId);

            if ($product === null) {
                $adjusted = true;

                continue;
            }

            $requested = $this->saleService->numeric((string) $item['quantity']);
            $clamped = $this->clampQuantityToStock($product, $requested);

            if ($clamped === null) {
                $adjusted = true;

                continue;
            }

            if ($clamped !== $requested) {
                $adjusted = true;
            }

            $result[] = [
                'product_id' => $productId,
                'quantity' => $this->saleService->formatQty($clamped),
            ];
        }

        return [$result, $adjusted];
    }

    private function clampQuantityToStock(Product $product, float $quantity): ?float
    {
        if ($quantity <= 0) {
            return null;
        }

        if ($this->saleService->allowsOverselling()) {
            return $quantity;
        }

        if ($product->stock_qty === null) {
            return $quantity;
        }

        $stock = (float) $product->stock_qty;

        if ($stock <= 0) {
            return null;
        }

        if ($quantity > $stock) {
            return $stock;
        }

        return $quantity;
    }
}
