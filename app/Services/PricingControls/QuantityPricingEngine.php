<?php

namespace App\Services\PricingControls;

use App\Models\Product;
use App\Models\QuantityChannelTier;
use App\Models\QuantityControl;
use Illuminate\Support\Collection;

class QuantityPricingEngine
{
    /** @var array<string, array{price_field: string, name_en: string, name_ur: string}> */
    public const PRICE_TIER_TYPES = [
        'retail' => [
            'price_field' => 'retail_price',
            'name_en' => 'Retail',
            'name_ur' => 'ریٹیل',
        ],
        'wholesale' => [
            'price_field' => 'wholesale_price',
            'name_en' => 'Wholesale',
            'name_ur' => 'ہول سیل',
        ],
        'super_wholesale' => [
            'price_field' => 'super_wholesale_price',
            'name_en' => 'Bulk',
            'name_ur' => 'بلک',
        ],
        'distributor' => [
            'price_field' => 'distributor_price',
            'name_en' => 'Distributor',
            'name_ur' => 'ڈسٹریبیوٹر',
        ],
    ];

    /** @var array<string, int> */
    public const TIER_SORT_ORDER = [
        'retail' => 1,
        'wholesale' => 2,
        'super_wholesale' => 3,
        'distributor' => 4,
    ];

    public function __construct(
        private readonly ChannelPricingEngine $priceEngine,
    ) {}

    public function activeProfile(): ?QuantityControl
    {
        return QuantityControl::query()
            ->where('status', 'active')
            ->with(['tiers' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('id')
            ->first();
    }

    /**
     * @return Collection<int, QuantityChannelTier>
     */
    public function activeTiers(): Collection
    {
        $profile = $this->activeProfile();

        if ($profile === null) {
            return collect();
        }

        return $this->sortTiers(
            $profile->tiers->filter(fn (QuantityChannelTier $tier): bool => $tier->isActive()),
        );
    }

    /**
     * @param  Collection<int, QuantityChannelTier>  $tiers
     * @return Collection<int, QuantityChannelTier>
     */
    public function sortTiers(Collection $tiers): Collection
    {
        return $tiers
            ->sortBy(fn (QuantityChannelTier $tier): int => self::TIER_SORT_ORDER[$tier->tier_key] ?? 99)
            ->values();
    }

    public function tierQuantityLabel(string $tierKey): string
    {
        return match ($tierKey) {
            'retail' => __('agricart.products.fields.retail_quantity'),
            'wholesale' => __('agricart.products.fields.wholesale_quantity'),
            'super_wholesale' => __('agricart.products.fields.super_wholesale_quantity'),
            'distributor' => __('agricart.products.fields.distributor_quantity'),
            default => __('agricart.products.fields.tier_qty', ['tier' => $this->tierTypeLabel($tierKey)]),
        };
    }

    public function sortOrderForTierKey(string $tierKey): int
    {
        return self::TIER_SORT_ORDER[$tierKey] ?? 99;
    }

    public function priceFieldForTierKey(string $tierKey): ?string
    {
        return self::PRICE_TIER_TYPES[$tierKey]['price_field'] ?? null;
    }

    public function tierTypeLabel(string $tierKey): string
    {
        $definition = self::PRICE_TIER_TYPES[$tierKey] ?? null;

        if ($definition === null) {
            return $tierKey;
        }

        if (app()->getLocale() === 'ur' && filled($definition['name_ur'])) {
            return (string) $definition['name_ur'];
        }

        return (string) $definition['name_en'];
    }

    public function priceFieldLabel(string $field): string
    {
        foreach (self::PRICE_TIER_TYPES as $tierKey => $definition) {
            if ($definition['price_field'] === $field) {
                return $this->tierTypeLabel($tierKey);
            }
        }

        return $this->priceEngine->productFieldLabel($field);
    }

    /**
     * @return array<string, int>
     */
    public function defaultQuantitiesByPriceField(): array
    {
        $quantities = [];

        foreach ($this->activeTiers() as $tier) {
            $quantities[$tier->price_field] = $tier->min_quantity;
        }

        return $quantities;
    }

    /**
     * @return list<array{price_field: string, tier_key: string, label: string, min_quantity: int}>
     */
    public function pricingRowsForForm(?Product $product = null): array
    {
        $overrides = is_array($product?->price_tier_quantities) ? $product->price_tier_quantities : [];
        $rows = [];

        foreach ($this->activeTiers() as $tier) {
            $rows[] = [
                'price_field' => $tier->price_field,
                'tier_key' => $tier->tier_key,
                'label' => $tier->localizedName(),
                'min_quantity' => (int) ($overrides[$tier->price_field] ?? $tier->min_quantity),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function tierTypeOptions(?int $ignoreTierId = null): array
    {
        $profile = $this->activeProfile();

        if ($profile === null) {
            return [];
        }

        $used = QuantityChannelTier::query()
            ->where('quantity_control_id', $profile->id)
            ->when($ignoreTierId !== null, fn ($query) => $query->where('id', '!=', $ignoreTierId))
            ->pluck('tier_key')
            ->all();

        $options = [];

        foreach (self::PRICE_TIER_TYPES as $tierKey => $definition) {
            if (in_array($tierKey, $used, true)) {
                continue;
            }

            $label = app()->getLocale() === 'ur' && filled($definition['name_ur'])
                ? $definition['name_ur']
                : $definition['name_en'];

            $options[] = [
                'value' => $tierKey,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * @return array{field: string, price: string|null, tier: QuantityChannelTier}|null
     */
    public function resolveForQuantity(Product $product, float $quantity): ?array
    {
        if ($quantity <= 0) {
            return null;
        }

        $overrides = is_array($product->price_tier_quantities) ? $product->price_tier_quantities : [];

        $rules = $this->activeTiers()
            ->map(fn (QuantityChannelTier $tier): array => [
                'min_quantity' => (int) ($overrides[$tier->price_field] ?? $tier->min_quantity),
                'price_field' => $tier->price_field,
                'sort_order' => $tier->sort_order,
                'tier' => $tier,
            ])
            ->sortBy([
                ['min_quantity', 'desc'],
                ['sort_order', 'asc'],
            ])
            ->values();

        foreach ($rules as $rule) {
            if ($quantity >= $rule['min_quantity']) {
                return [
                    'field' => $rule['price_field'],
                    'price' => $this->productPrice($product, $rule['price_field']),
                    'tier' => $rule['tier'],
                ];
            }
        }

        return null;
    }

    public function productPrice(Product $product, string $priceField): ?string
    {
        if ($priceField === 'retail_price') {
            return filled($product->retail_price) ? (string) $product->retail_price : null;
        }

        if (str_starts_with($priceField, 'channel:')) {
            $key = substr($priceField, 8);
            $channelPrices = is_array($product->channel_prices) ? $product->channel_prices : [];

            return isset($channelPrices[$key]) ? (string) $channelPrices[$key] : null;
        }

        if (in_array($priceField, ChannelPricingEngine::CORE_PRODUCT_FIELDS, true)) {
            $value = $product->{$priceField};

            return filled($value) ? (string) $value : null;
        }

        return null;
    }

    public function formatQuantity(int $quantity): string
    {
        return number_format($quantity);
    }
}
