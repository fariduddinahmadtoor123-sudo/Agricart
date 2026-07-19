<?php

namespace App\Services\PricingControls;

use App\Models\Product;
use App\Models\QuantityChannelTier;
use App\Models\QuantityControl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class QuantityPricingEngine
{
    private ?QuantityControl $activeProfileMemo = null;

    private bool $activeProfileLoaded = false;

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
        if ($this->activeProfileLoaded) {
            return $this->activeProfileMemo;
        }

        $payload = Cache::remember('quantity-pricing.active-profile.v1', now()->addMinutes(30), function (): ?array {
            $profile = QuantityControl::query()
                ->where('status', 'active')
                ->with(['tiers' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
                ->orderBy('id')
                ->first();

            if ($profile === null) {
                return null;
            }

            return [
                'profile' => $profile->getAttributes(),
                'tiers' => $profile->tiers->map(
                    static fn (QuantityChannelTier $tier): array => $tier->getAttributes(),
                )->all(),
            ];
        });

        $this->activeProfileLoaded = true;

        if ($payload === null) {
            return $this->activeProfileMemo = null;
        }

        $profile = (new QuantityControl)->setRawAttributes($payload['profile'], true);
        $profile->exists = true;
        $tiers = collect($payload['tiers'])->map(function (array $attributes): QuantityChannelTier {
            $tier = (new QuantityChannelTier)->setRawAttributes($attributes, true);
            $tier->exists = true;

            return $tier;
        });
        $profile->setRelation('tiers', $tiers);

        return $this->activeProfileMemo = $profile;
    }

    public function clearCache(): void
    {
        Cache::forget('quantity-pricing.active-profile.v1');
        $this->activeProfileLoaded = false;
        $this->activeProfileMemo = null;
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

    /** @var list<string> Quantity tiers not used on in-store POS (e.g. online wholesale). */
    public const POS_EXCLUDED_TIER_KEYS = ['wholesale'];

    /**
     * @return array{field: string, price: string|null, tier: QuantityChannelTier}|null
     */
    public function resolveForQuantity(Product $product, float $quantity, bool $forPos = false): ?array
    {
        if ($quantity <= 0) {
            return null;
        }

        $overrides = is_array($product->price_tier_quantities) ? $product->price_tier_quantities : [];

        $tiers = $this->activeTiers();

        if ($forPos) {
            $tiers = $tiers->filter(
                fn (QuantityChannelTier $tier): bool => ! in_array($tier->tier_key, self::POS_EXCLUDED_TIER_KEYS, true),
            );
        }

        $rules = $tiers
            ->map(fn (QuantityChannelTier $tier): array => [
                'min_quantity' => (int) ($overrides[$tier->price_field] ?? $tier->min_quantity),
                'price_field' => $tier->price_field,
                'sort_order' => $tier->sort_order,
                'tier' => $tier,
                'tier_rank' => self::TIER_SORT_ORDER[$tier->tier_key] ?? 0,
            ])
            ->values();

        $match = $rules
            ->filter(fn (array $rule): bool => $quantity >= $rule['min_quantity'])
            ->sortByDesc(fn (array $rule): int => ($rule['tier_rank'] * 1_000_000) + $rule['min_quantity'])
            ->first();

        if ($match === null) {
            return null;
        }

        return [
            'field' => $match['price_field'],
            'price' => $this->productPrice($product, $match['price_field']),
            'tier' => $match['tier'],
        ];
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
