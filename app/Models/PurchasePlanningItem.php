<?php

namespace App\Models;

use App\Services\Purchasing\PurchasePlanningService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchasePlanningItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_planning_sheet_id',
        'product_id',
        'sort_order',
        'product_name_en',
        'product_name_ur',
        'image_path',
        'stock_qty',
        'required_qty',
        'low_qty',
        'purchase_qty',
        'landing_price',
        'sale_price',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(PurchasePlanningSheet::class, 'purchase_planning_sheet_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(?string $locale = null): string
    {
        $locale = app(PurchasePlanningService::class)->normalizeLocale(
            $locale ?? $this->sheet?->displayLocale() ?? app()->getLocale(),
        );

        if ($locale === 'ur' && filled($this->product_name_ur)) {
            return (string) $this->product_name_ur;
        }

        return (string) $this->product_name_en;
    }

    public function imageUrl(): ?string
    {
        return filled($this->image_path)
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }
}
