<?php

namespace App\Models;

use App\Services\Purchasing\PurchaseQuotationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseQuotationItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_quotation_id',
        'product_id',
        'sort_order',
        'product_name_en',
        'product_name_ur',
        'image_path',
        'required_qty',
        'previous_price',
        'current_price',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuotation::class, 'purchase_quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(?string $locale = null): string
    {
        $locale = app(PurchaseQuotationService::class)->normalizeLocale(
            $locale ?? $this->quotation?->displayLocale() ?? app()->getLocale(),
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
