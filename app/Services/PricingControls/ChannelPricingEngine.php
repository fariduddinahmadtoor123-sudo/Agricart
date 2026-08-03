<?php

namespace App\Services\PricingControls;

use App\Models\CompanyProfile;
use App\Models\PriceChannelTier;
use App\Models\PriceControl;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChannelPricingEngine
{
    /** @var list<string> */
    public const CORE_PRODUCT_FIELDS = [
        'wholesale_price',
        'super_wholesale_price',
        'distributor_price',
    ];

    /** @var array<string, array{price_field: string, name_en: string, name_ur: string}> */
    public const PRICE_TIER_TYPES = [
        'retail' => [
            'price_field' => 'retail_price',
            'name_en' => 'Retail price',
            'name_ur' => 'ریٹیل قیمت',
        ],
        'wholesale' => [
            'price_field' => 'wholesale_price',
            'name_en' => 'Wholesale price',
            'name_ur' => 'ہول سیل قیمت',
        ],
        'super_wholesale' => [
            'price_field' => 'super_wholesale_price',
            'name_en' => 'Bulk price',
            'name_ur' => 'بلک قیمت',
        ],
        'distributor' => [
            'price_field' => 'distributor_price',
            'name_en' => 'Distributor price',
            'name_ur' => 'ڈسٹریبیوٹر قیمت',
        ],
    ];

    /** @var array<string, int> */
    public const TIER_SORT_ORDER = [
        'retail' => 1,
        'wholesale' => 2,
        'super_wholesale' => 3,
        'distributor' => 4,
    ];

    public function activeProfile(): ?PriceControl
    {
        return PriceControl::query()
            ->where('status', 'active')
            ->with(['tiers' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('id')
            ->first();
    }

    /**
     * @return Collection<int, PriceChannelTier>
     */
    public function activeTiers(): Collection
    {
        $profile = $this->activeProfile();

        if ($profile === null) {
            return collect();
        }

        return $this->sortTiers(
            $profile->tiers->filter(fn (PriceChannelTier $tier): bool => $tier->isActive()),
        );
    }

    /**
     * @return Collection<int, PriceChannelTier>
     */
    public function markupTiers(): Collection
    {
        return $this->activeTiers()->reject(fn (PriceChannelTier $tier): bool => $tier->isManual())->values();
    }

    /**
     * @param  Collection<int, PriceChannelTier>  $tiers
     * @return Collection<int, PriceChannelTier>
     */
    public function sortTiers(Collection $tiers): Collection
    {
        return $tiers
            ->sortBy(fn (PriceChannelTier $tier): int => self::TIER_SORT_ORDER[$tier->tier_key] ?? 99)
            ->values();
    }

    public function tierPriceLabel(string $tierKey): string
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

    /**
     * @return Collection<int, PriceChannelTier>
     */
    public function listTiers(): Collection
    {
        $profile = $this->activeProfile();

        if ($profile === null) {
            return collect();
        }

        return $this->sortTiers($profile->tiers);
    }

    /**
     * @return array<string, string>
     */
    public function computePrices(float $purchasePrice): array
    {
        $prices = [];

        foreach ($this->markupTiers() as $tier) {
            $prices[$tier->tier_key] = $this->computeTierPrice($purchasePrice, $tier);
        }

        return $prices;
    }

    /**
     * All active tiers including retail (markup %). Used by Price Update sheet — not purchases.
     *
     * @return array<string, string> tier_key => formatted price
     */
    public function computeAllTierPrices(float $purchasePrice): array
    {
        $prices = [];

        foreach ($this->activeTiers() as $tier) {
            $prices[$tier->tier_key] = $this->computeTierPrice($purchasePrice, $tier);
        }

        return $prices;
    }

    public function computeTierPrice(float $purchasePrice, PriceChannelTier $tier): string
    {
        $markup = (float) $tier->markup_percent;
        $amount = $purchasePrice * (1 + ($markup / 100));

        return $this->formatPrice($amount);
    }

    public function applyToProduct(Product $product, float $purchasePrice, ?string $retailPrice = null): void
    {
        if ($purchasePrice <= 0) {
            return;
        }

        $updates = [];
        $channelPrices = is_array($product->channel_prices) ? $product->channel_prices : [];

        if ($retailPrice !== null && trim($retailPrice) !== '') {
            $updates['retail_price'] = $this->formatPrice($retailPrice);
        }

        foreach ($this->markupTiers() as $tier) {
            $formatted = $this->computeTierPrice($purchasePrice, $tier);

            if (str_starts_with($tier->product_field, 'channel:')) {
                $channelPrices[substr($tier->product_field, 8)] = $formatted;
            } elseif (in_array($tier->product_field, self::CORE_PRODUCT_FIELDS, true)) {
                $updates[$tier->product_field] = $formatted;
            }
        }

        if ($channelPrices !== []) {
            $updates['channel_prices'] = $channelPrices;
        }

        if ($updates !== []) {
            $product->update($updates);
        }
    }

    /**
     * Apply buy price markup to every active tier including retail. Does not touch stock.
     */
    public function applyBuyPriceToProduct(Product $product, float $purchasePrice): void
    {
        if ($purchasePrice <= 0) {
            return;
        }

        $updates = [];
        $channelPrices = is_array($product->channel_prices) ? $product->channel_prices : [];

        foreach ($this->activeTiers() as $tier) {
            $formatted = $this->computeTierPrice($purchasePrice, $tier);

            if (str_starts_with($tier->product_field, 'channel:')) {
                $channelPrices[substr($tier->product_field, 8)] = $formatted;
            } elseif ($tier->product_field === 'retail_price' || in_array($tier->product_field, self::CORE_PRODUCT_FIELDS, true)) {
                $updates[$tier->product_field] = $formatted;
            }
        }

        if ($channelPrices !== []) {
            $updates['channel_prices'] = $channelPrices;
        }

        if ($updates !== []) {
            $product->update($updates);
        }
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function productFieldOptions(?int $ignoreTierId = null): array
    {
        $profile = $this->activeProfile();

        if ($profile === null) {
            return [];
        }

        $used = PriceChannelTier::query()
            ->where('price_control_id', $profile->id)
            ->when($ignoreTierId !== null, fn ($query) => $query->where('id', '!=', $ignoreTierId))
            ->pluck('product_field')
            ->all();

        $options = [
            ['value' => 'wholesale_price', 'label' => __('price_controls.fields.map_wholesale')],
            ['value' => 'super_wholesale_price', 'label' => __('price_controls.fields.map_super_wholesale')],
            ['value' => 'distributor_price', 'label' => __('price_controls.fields.map_distributor')],
        ];

        $available = array_values(array_filter(
            $options,
            fn (array $option): bool => ! in_array($option['value'], $used, true),
        ));

        if ($available === []) {
            $available[] = [
                'value' => '__custom__',
                'label' => __('price_controls.fields.map_custom_slot'),
            ];
        }

        return $available;
    }

    public function productFieldLabel(string $field): string
    {
        if (str_starts_with($field, 'channel:')) {
            return __('price_controls.fields.map_custom', ['key' => substr($field, 8)]);
        }

        return match ($field) {
            'retail_price' => __('price_controls.fields.map_retail'),
            'wholesale_price' => __('price_controls.fields.map_wholesale'),
            'super_wholesale_price' => __('price_controls.fields.map_super_wholesale'),
            'distributor_price' => __('price_controls.fields.map_distributor'),
            default => $field,
        };
    }

    public function formatPrice(float|string $amount): string
    {
        $profile = CompanyProfile::current();
        $value = (float) str_replace(',', '', (string) $amount);

        if (! $profile->price_use_decimals) {
            return (string) (int) round($value);
        }

        $places = max(0, (int) $profile->price_decimal_places);
        $formatted = number_format($value, $places, '.', '');

        if ($places === 0 || ! str_contains($formatted, '.')) {
            return $formatted;
        }

        // Only trim fractional trailing zeros — never strip zeros from the integer part.
        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    public function examplePurchasePrice(): float
    {
        return 100.0;
    }
}
