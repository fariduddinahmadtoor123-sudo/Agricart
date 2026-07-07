<?php

namespace App\Models;

use App\Services\Sales\SaleQuotationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleQuotationItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'sale_quotation_id',
        'product_id',
        'sort_order',
        'product_name_en',
        'product_name_ur',
        'brand_name_en',
        'brand_name_ur',
        'unit_label',
        'image_path',
        'quantity',
        'unit_price',
        'price_tier_key',
        'line_total',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SaleQuotation::class, 'sale_quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(?string $locale = null): string
    {
        $locale = app(SaleQuotationService::class)->normalizeLocale(
            $locale ?? $this->quotation?->displayLocale() ?? app()->getLocale(),
        );

        if ($locale === 'ur' && filled($this->product_name_ur)) {
            return (string) $this->product_name_ur;
        }

        return (string) $this->product_name_en;
    }

    public function displayBrand(?string $locale = null): string
    {
        $locale = app(SaleQuotationService::class)->normalizeLocale(
            $locale ?? $this->quotation?->displayLocale() ?? app()->getLocale(),
        );

        if ($locale === 'ur' && filled($this->brand_name_ur)) {
            return (string) $this->brand_name_ur;
        }

        $brand = (string) ($this->brand_name_en ?? '');

        return $brand !== '' ? $brand : '—';
    }
}
