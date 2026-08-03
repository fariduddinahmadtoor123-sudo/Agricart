<?php

namespace App\Services\PricingControls;

use App\Models\Product;
use App\Services\Catalog\CatalogCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductPriceUpdateService
{
    public function __construct(
        private readonly ChannelPricingEngine $priceEngine,
        private readonly QuantityPricingEngine $quantityEngine,
    ) {}

    /**
     * @param  array{
     *     last_buy_price: mixed,
     *     retail_price?: mixed,
     *     wholesale_price?: mixed,
     *     super_wholesale_price?: mixed,
     *     distributor_price?: mixed,
     *     channel_prices?: array<string, mixed>|null,
     *     price_tier_quantities?: array<string, mixed>|null
     * }  $data
     */
    public function updateProductPricing(Product $product, array $data): Product
    {
        $buy = $this->numeric($data['last_buy_price'] ?? null);

        if ($buy === null || $buy <= 0) {
            throw ValidationException::withMessages([
                'last_buy_price' => __('price_updates.messages.buy_price_required'),
            ]);
        }

        $updates = [
            'last_buy_price' => $this->priceEngine->formatPrice($buy),
        ];

        foreach (['retail_price', 'wholesale_price', 'super_wholesale_price', 'distributor_price'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if ($value === null || trim((string) $value) === '') {
                $updates[$field] = null;

                continue;
            }

            $numeric = $this->numeric($value);

            if ($numeric === null || $numeric < 0) {
                throw ValidationException::withMessages([
                    $field => __('price_updates.messages.invalid_price', [
                        'field' => $this->priceEngine->productFieldLabel($field),
                    ]),
                ]);
            }

            $updates[$field] = $this->priceEngine->formatPrice($numeric);
        }

        if (array_key_exists('channel_prices', $data) && is_array($data['channel_prices'])) {
            $channelPrices = [];

            foreach ($data['channel_prices'] as $key => $value) {
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $numeric = $this->numeric($value);

                if ($numeric === null || $numeric < 0) {
                    continue;
                }

                $channelPrices[(string) $key] = $this->priceEngine->formatPrice($numeric);
            }

            $updates['channel_prices'] = $channelPrices;
        }

        if (array_key_exists('price_tier_quantities', $data)) {
            $updates['price_tier_quantities'] = $this->normalizeQuantities(
                is_array($data['price_tier_quantities']) ? $data['price_tier_quantities'] : [],
            );
        }

        return DB::transaction(function () use ($product, $updates): Product {
            $product->update($updates);
            CatalogCache::forgetOverviewStats();

            return $product->fresh();
        });
    }

    /**
     * Recalculate channel prices from buy price using Price Channel markups (including retail).
     *
     * @return array{
     *     retail_price: string|null,
     *     wholesale_price: string|null,
     *     super_wholesale_price: string|null,
     *     distributor_price: string|null,
     *     channel_prices: array<string, string>
     * }
     */
    public function pricesFromBuyPrice(float $buyPrice): array
    {
        $result = [
            'retail_price' => null,
            'wholesale_price' => null,
            'super_wholesale_price' => null,
            'distributor_price' => null,
            'channel_prices' => [],
        ];

        $coreFields = ['retail_price', 'wholesale_price', 'super_wholesale_price', 'distributor_price'];

        foreach ($this->priceEngine->activeTiers() as $tier) {
            $formatted = $this->priceEngine->computeTierPrice($buyPrice, $tier);
            $field = (string) $tier->product_field;

            if (str_starts_with($field, 'channel:')) {
                $result['channel_prices'][substr($field, 8)] = $formatted;

                continue;
            }

            if (in_array($field, $coreFields, true)) {
                $result[$field] = $formatted;
            }
        }

        return $result;
    }

    /**
     * Live Quantity Channel thresholds (Marketplace → Quantity Channel).
     *
     * @return array<string, int>
     */
    public function channelQuantities(): array
    {
        app(QuantityControlService::class)->ensureDefaultProfile();
        $this->quantityEngine->clearCache();

        $quantities = [];

        foreach ($this->quantityEngine->activeTiers() as $tier) {
            $field = (string) $tier->price_field;

            if ($field === '') {
                continue;
            }

            $quantities[$field] = max(1, (int) $tier->min_quantity);
        }

        return $quantities;
    }

    /**
     * Starting quantities for Price Update sheet:
     * Quantity Channel defaults, with per-product overrides when already saved.
     *
     * @return array<string, int>
     */
    public function quantitiesForPriceUpdateSheet(?Product $product = null): array
    {
        return $this->defaultQuantitiesForProduct($product);
    }

    /**
     * @return array<string, int>
     */
    public function defaultQuantitiesForProduct(?Product $product = null): array
    {
        $defaults = $this->channelQuantities();
        $overrides = is_array($product?->price_tier_quantities) ? $product->price_tier_quantities : [];

        if ($overrides === []) {
            return $defaults;
        }

        $merged = $defaults;

        foreach ($overrides as $field => $qty) {
            $field = (string) $field;
            $value = (int) $qty;

            if ($value < 1) {
                continue;
            }

            $merged[$field] = $value;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $quantities
     * @return array<string, int>
     */
    private function normalizeQuantities(array $quantities): array
    {
        $allowed = array_keys($this->quantityEngine->defaultQuantitiesByPriceField());
        $normalized = [];

        foreach ($quantities as $field => $qty) {
            $field = (string) $field;

            if ($allowed !== [] && ! in_array($field, $allowed, true)) {
                continue;
            }

            $value = (int) $qty;

            if ($value < 1) {
                throw ValidationException::withMessages([
                    'price_tier_quantities' => __('price_updates.messages.invalid_quantity'),
                ]);
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }

    private function numeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '', trim($value));
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
