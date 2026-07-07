<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuantityChannelTier extends Model
{
    protected $fillable = [
        'quantity_control_id',
        'tier_key',
        'name_en',
        'name_ur',
        'min_quantity',
        'price_field',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function quantityControl(): BelongsTo
    {
        return $this->belongsTo(QuantityControl::class);
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
}
