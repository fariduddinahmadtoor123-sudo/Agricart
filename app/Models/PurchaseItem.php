<?php

namespace App\Models;

use App\Services\Purchasing\PurchaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_id',
        'product_id',
        'sort_order',
        'product_name_en',
        'product_name_ur',
        'image_path',
        'required_qty',
        'purchase_qty',
        'received_qty',
        'previous_price',
        'current_rate',
        'landing_cost',
        'cost_price',
        'sale_price',
        'line_total',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(?string $locale = null): string
    {
        $locale = app(PurchaseService::class)->normalizeLocale(
            $locale ?? $this->purchase?->displayLocale() ?? app()->getLocale(),
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
