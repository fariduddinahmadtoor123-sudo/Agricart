<?php

namespace App\Services\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Sales\SaleService;
use Illuminate\Support\Collection;

class StorePackageService
{
    public function __construct(
        private readonly QuantityPricingEngine $quantityPricing,
        private readonly SaleService $saleService,
        private readonly ChannelPricingEngine $priceEngine,
    ) {}

    /**
     * @return Collection<int, ProductPackage>
     */
    public function packagesForCategory(Category $category): Collection
    {
        return ProductPackage::query()
            ->where('status', ProductPackage::STATUS_ACTIVE)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->with(['items.product.brand', 'items.product.unit'])
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();
    }

    public function findStorefrontBySlug(string $slug): ?ProductPackage
    {
        return ProductPackage::query()
            ->where('status', ProductPackage::STATUS_ACTIVE)
            ->where('slug', $slug)
            ->with(['items.product.brand', 'items.product.unit', 'items.product.packUnit', 'categories'])
            ->first();
    }

    public function localizedName(ProductPackage $package, ?string $locale = null): string
    {
        return $package->displayName($locale);
    }

    public function imageUrl(ProductPackage $package): ?string
    {
        return $package->imageUrl();
    }

    /**
     * Build the on-page bill for a package at the given package quantity multiplier.
     *
     * @param  array<int, float>  $quantityOverrides  product_id => quantity
     * @return array{
     *     lines: list<array{
     *         product: Product,
     *         quantity: float,
     *         unit_price: float,
     *         unit_price_formatted: string,
     *         line_total: float,
     *         line_total_formatted: string,
     *         price_tier_key: string
     *     }>,
     *     subtotal: float,
     *     discount_percent: float,
     *     discount_amount: float,
     *     total: float,
     *     subtotal_formatted: string,
     *     discount_formatted: string,
     *     total_formatted: string
     * }
     */
    public function buildBill(ProductPackage $package, float $packageQty = 1, array $quantityOverrides = []): array
    {
        $packageQty = max(1, $packageQty);
        $discountPercent = max(0, min(100, (float) $package->discount_percent));
        $lines = [];
        $subtotal = 0.0;

        foreach ($package->items as $item) {
            $product = $item->product;

            if ($product === null || $product->status !== 'active') {
                continue;
            }

            $baseQty = array_key_exists($product->id, $quantityOverrides)
                ? (float) $quantityOverrides[$product->id]
                : (float) $item->quantity;
            $qty = round(max(0, $baseQty) * $packageQty, 3);

            if ($qty <= 0) {
                continue;
            }

            $resolved = $this->quantityPricing->resolveForQuantity($product, $qty, forPos: false);
            $unitPrice = (float) $this->saleService->numeric(
                (string) ($resolved['price'] ?? $product->wholesale_price ?? $product->retail_price ?? '0'),
            );
            $lineTotal = round($unitPrice * $qty, 2);
            $subtotal += $lineTotal;

            $lines[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'unit_price_formatted' => $this->priceEngine->formatPrice($unitPrice),
                'line_total' => $lineTotal,
                'line_total_formatted' => $this->priceEngine->formatPrice($lineTotal),
                'price_tier_key' => (string) ($resolved['tier']->tier_key ?? 'wholesale'),
            ];
        }

        $discountAmount = round($subtotal * ($discountPercent / 100), 2);
        $total = round(max(0, $subtotal - $discountAmount), 2);

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'subtotal_formatted' => $this->priceEngine->formatPrice($subtotal),
            'discount_formatted' => $this->priceEngine->formatPrice($discountAmount),
            'total_formatted' => $this->priceEngine->formatPrice($total),
        ];
    }
}
