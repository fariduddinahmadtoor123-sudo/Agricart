<?php

namespace App\Models;

use App\Services\Sales\SaleService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'sale_id',
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
        'unit_cost_snapshot',
        'cogs_snapshot',
        'costing_policy',
        'price_tier_key',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost_snapshot' => 'decimal:4',
            'cogs_snapshot' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SaleItem $item): void {
            if ($item->unit_cost_snapshot !== null) {
                return;
            }

            $saleDate = Sale::query()->whereKey($item->sale_id)->value('sale_date');
            $cost = app(\App\Services\Sales\HistoricalCostingService::class)->resolve(
                (int) $item->product_id,
                (string) ($saleDate ?: now()->toDateString()),
            );
            $unitCost = $cost['unit_cost'] ?? 0.0;

            $item->unit_cost_snapshot = $unitCost;
            $item->cogs_snapshot = round((float) $item->quantity * $unitCost, 4);
            $item->costing_policy = $cost['policy'] ?? 'no_purchase_history_v1';
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(?string $locale = null): string
    {
        $locale = app(SaleService::class)->normalizeLocale(
            $locale ?? $this->sale?->displayLocale() ?? app()->getLocale(),
        );

        if ($locale === 'ur' && filled($this->product_name_ur)) {
            return (string) $this->product_name_ur;
        }

        return (string) $this->product_name_en;
    }

    public function displayBrand(?string $locale = null): string
    {
        $locale = app(SaleService::class)->normalizeLocale(
            $locale ?? $this->sale?->displayLocale() ?? app()->getLocale(),
        );

        if ($locale === 'ur' && filled($this->brand_name_ur)) {
            return (string) $this->brand_name_ur;
        }

        $brand = (string) ($this->brand_name_en ?? '');

        return $brand !== '' ? $brand : '—';
    }
}
