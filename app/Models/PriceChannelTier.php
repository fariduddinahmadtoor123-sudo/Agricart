<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceChannelTier extends Model
{
    protected $fillable = [
        'price_control_id',
        'tier_key',
        'name_en',
        'name_ur',
        'markup_percent',
        'product_field',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'markup_percent' => 'decimal:3',
            'sort_order' => 'integer',
        ];
    }

    public function priceControl(): BelongsTo
    {
        return $this->belongsTo(PriceControl::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'ur' && filled($this->name_ur)) {
            return (string) $this->name_ur;
        }

        return (string) $this->name_en;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isManual(): bool
    {
        return $this->tier_key === 'retail';
    }
}
