<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'product_id',
        'sort_order',
        'product_name_en',
        'product_name_ur',
        'brand_name_en',
        'brand_name_ur',
        'unit_label',
        'image_path',
        'sold_quantity',
        'unit_price',
        'return_quantity',
        'return_amount',
    ];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'ur' && filled($this->product_name_ur)) {
            return (string) $this->product_name_ur;
        }

        return (string) $this->product_name_en;
    }

    public function displayBrand(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'ur' && filled($this->brand_name_ur)) {
            return (string) $this->brand_name_ur;
        }

        $brand = (string) ($this->brand_name_en ?? '');

        return $brand !== '' ? $brand : '—';
    }
}
