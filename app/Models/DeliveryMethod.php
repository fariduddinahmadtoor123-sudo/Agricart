<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryMethod extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_en',
        'name_ur',
        'description_en',
        'description_ur',
        'slug',
        'sort_order',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function rule(): HasOne
    {
        return $this->hasOne(DeliveryMethodRule::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_delivery_method')
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'ur' && filled($this->name_ur)) {
            return $this->name_ur;
        }

        return $this->name_en;
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $locale === 'ur' && filled($this->description_ur)
            ? $this->description_ur
            : $this->description_en;

        return filled($value) ? (string) $value : null;
    }
}
