<?php

namespace App\Services\Store;

use App\Models\CatalogControl;
use App\Models\Product;
use App\Models\QuantityChannelTier;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Sales\SaleService;
use Illuminate\Support\Facades\Storage;

class StorefrontProductPresenter
{
    public function __construct(
        private readonly StoreProductService $products,
        private readonly QuantityPricingEngine $quantityPricing,
        private readonly ChannelPricingEngine $priceEngine,
        private readonly SaleService $saleService,
    ) {}

    public function localizedBrandName(Product $product): ?string
    {
        $brand = $product->brand;

        if ($brand === null) {
            return null;
        }

        if (app()->getLocale() === 'ur' && filled($brand->name_ur)) {
            return $brand->name_ur;
        }

        return $brand->name_en;
    }

    public function brandImageUrl(Product $product): ?string
    {
        $brand = $product->brand;

        if ($brand === null || ! filled($brand->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($brand->image_path);
    }

    /**
     * @return array{amount: string, unit_price: string, formatted: string, tier_key: string, tier_label: string}|null
     */
    public function priceForQuantity(Product $product, float $quantity): ?array
    {
        if ($quantity <= 0) {
            $quantity = 1;
        }

        $resolved = $this->quantityPricing->resolveForQuantity($product, $quantity, forPos: false);
        $price = $resolved['price'] ?? $product->retail_price;

        if ($price === null || $price === '') {
            return null;
        }

        $numeric = $this->saleService->numeric((string) $price);
        $tier = $resolved['tier'] ?? null;
        $tierKey = $tier instanceof QuantityChannelTier ? $tier->tier_key : 'retail';

        return [
            'amount' => number_format($numeric, 2, '.', ''),
            'unit_price' => $this->saleService->formatMoney($numeric),
            'formatted' => $this->priceEngine->formatPrice($numeric),
            'tier_key' => $tierKey,
            'tier_label' => $this->tierLabel($tierKey),
        ];
    }

    /**
     * @return list<array{min_qty: int, tier_label: string, price: string, formatted: string}>
     */
    public function quantityPriceTable(Product $product): array
    {
        $overrides = is_array($product->price_tier_quantities) ? $product->price_tier_quantities : [];
        $rows = [];

        foreach ($this->quantityPricing->activeTiers() as $tier) {
            $minQty = (int) ($overrides[$tier->price_field] ?? $tier->min_quantity);
            $price = $this->quantityPricing->productPrice($product, $tier->price_field);

            if ($price === null || $price === '') {
                continue;
            }

            $numeric = $this->saleService->numeric($price);
            $rows[] = [
                'min_qty' => $minQty,
                'tier_label' => $this->tierLabel($tier->tier_key),
                'price' => $this->saleService->formatMoney($numeric),
                'formatted' => $this->priceEngine->formatPrice($numeric),
            ];
        }

        if ($rows === [] && filled($product->retail_price)) {
            $numeric = $this->saleService->numeric((string) $product->retail_price);
            $rows[] = [
                'min_qty' => 1,
                'tier_label' => $this->tierLabel('retail'),
                'price' => $this->saleService->formatMoney($numeric),
                'formatted' => $this->priceEngine->formatPrice($numeric),
            ];
        }

        return $rows;
    }

    public function localizedControlName(CatalogControl $control): string
    {
        if (app()->getLocale() === 'ur' && filled($control->name_ur)) {
            return $control->name_ur;
        }

        return $control->name_en;
    }

    public function localizedControlValue(CatalogControl $control): string
    {
        if (app()->getLocale() === 'ur' && filled($control->value_ur)) {
            return $control->value_ur;
        }

        return (string) ($control->value_en ?? '');
    }

    public function unitPackSummary(Product $product): ?string
    {
        $parts = [];

        if ($product->unit) {
            $unitName = app()->getLocale() === 'ur' && filled($product->unit->name_ur)
                ? $product->unit->name_ur
                : $product->unit->name_en;

            if (filled($product->unit_qty)) {
                $parts[] = trim($product->unit_qty . ' ' . $unitName);
            } else {
                $parts[] = $unitName;
            }
        }

        if ($product->packUnit && filled($product->pack_qty)) {
            $packName = app()->getLocale() === 'ur' && filled($product->packUnit->name_ur)
                ? $product->packUnit->name_ur
                : $product->packUnit->name_en;
            $parts[] = trim($product->pack_qty . ' ' . $packName);
        }

        return $parts !== [] ? implode(' · ', $parts) : null;
    }

    /**
     * @return list<array{key: string, label: string, value: string}>
     */
    public function identityFacts(Product $product): array
    {
        $facts = [];

        if (filled($product->sku)) {
            $facts[] = ['key' => 'sku', 'label' => __('agricart.storefront.product.facts.sku'), 'value' => $product->sku];
        }

        if (filled($product->code)) {
            $facts[] = ['key' => 'code', 'label' => __('agricart.storefront.product.facts.code'), 'value' => $product->code];
        }

        if ($pack = $this->unitPackSummary($product)) {
            $facts[] = ['key' => 'unit', 'label' => __('agricart.storefront.product.facts.unit'), 'value' => $pack];
        }

        return $facts;
    }

    /**
     * @return list<array{title: string, html: string}>
     */
    public function contentSections(Product $product): array
    {
        $sections = [];

        $map = [
            'full_description' => __('agricart.storefront.product.description'),
            'usage_guidelines' => __('agricart.storefront.product.sections.usage'),
            'benefits' => __('agricart.storefront.product.sections.benefits'),
            'warnings' => __('agricart.storefront.product.sections.warnings'),
            'buying_guide' => __('agricart.storefront.product.sections.buying_guide'),
            'customs_notes' => __('agricart.categories.fields.customs_notes'),
            'import_notes' => __('agricart.categories.fields.import_notes'),
            'export_notes' => __('agricart.categories.fields.export_notes'),
        ];

        foreach ($map as $base => $title) {
            $text = $this->products->localizedField($product, $base);

            if (filled($text)) {
                $sections[] = [
                    'title' => $title,
                    'html' => nl2br(e($text)),
                ];
            }
        }

        foreach ($product->controls ?? [] as $control) {
            $value = $this->localizedControlValue($control);

            if (! filled($value)) {
                continue;
            }

            $sections[] = [
                'title' => $this->localizedControlName($control),
                'html' => nl2br(e($value)),
            ];
        }

        if (filled($product->hs_code)) {
            $sections[] = [
                'title' => __('agricart.categories.fields.hs_code'),
                'html' => e($product->hs_code),
            ];
        }

        return $sections;
    }

    public function localizedBrandDescription(Product $product): ?string
    {
        $brand = $product->brand;

        if ($brand === null) {
            return null;
        }

        if (app()->getLocale() === 'ur' && filled($brand->description_ur)) {
            return $brand->description_ur;
        }

        if (filled($brand->description_en)) {
            return $brand->description_en;
        }

        if (filled($brand->description_ur)) {
            return $brand->description_ur;
        }

        return null;
    }

    public function hasBrandAbout(Product $product): bool
    {
        return filled($this->localizedBrandDescription($product));
    }

    public function hasAgricartBrandReview(Product $product): bool
    {
        $rating = $product->brand?->agricart_rating;

        if (! is_int($rating) || $rating < 0 || $rating > 10) {
            return false;
        }

        $criteria = \App\Support\Catalog\BrandAgricartReviewCriteria::normalize(
            $product->brand?->agricart_rating_criteria,
        );

        return \App\Support\Catalog\BrandAgricartReviewCriteria::isComplete($criteria);
    }

    public function localizedAgricartRatingReason(Product $product): ?string
    {
        $brand = $product->brand;

        if ($brand === null) {
            return null;
        }

        if (app()->getLocale() === 'ur' && filled($brand->agricart_rating_reason_ur)) {
            return $brand->agricart_rating_reason_ur;
        }

        if (filled($brand->agricart_rating_reason_en)) {
            return $brand->agricart_rating_reason_en;
        }

        if (filled($brand->agricart_rating_reason_ur)) {
            return $brand->agricart_rating_reason_ur;
        }

        return null;
    }

    public function localizedAgricartRatingRevision(Product $product): ?string
    {
        $brand = $product->brand;

        if ($brand === null) {
            return null;
        }

        if (app()->getLocale() === 'ur' && filled($brand->agricart_rating_revision_ur)) {
            return $brand->agricart_rating_revision_ur;
        }

        if (filled($brand->agricart_rating_revision_en)) {
            return $brand->agricart_rating_revision_en;
        }

        if (filled($brand->agricart_rating_revision_ur)) {
            return $brand->agricart_rating_revision_ur;
        }

        return null;
    }

    public function brandReviewNoticeText(): string
    {
        return \App\Models\StoreSetting::current()->brandReviewNoticeText();
    }

    private function tierLabel(string $tierKey): string
    {
        $definition = QuantityPricingEngine::PRICE_TIER_TYPES[$tierKey] ?? null;

        if ($definition === null) {
            return $tierKey;
        }

        if (app()->getLocale() === 'ur' && filled($definition['name_ur'])) {
            return $definition['name_ur'];
        }

        return $definition['name_en'];
    }
}
