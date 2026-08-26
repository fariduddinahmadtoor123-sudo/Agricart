<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProductPackage extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /** @var list<string> */
    protected $fillable = [
        'package_number',
        'slug',
        'name_en',
        'name_ur',
        'image_path',
        'status',
        'discount_percent',
        'sort_order',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function displayName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'ur' && filled($this->name_ur)) {
            return (string) $this->name_ur;
        }

        return (string) ($this->name_en ?: $this->name_ur ?: $this->package_number);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function imageUrl(): ?string
    {
        return filled($this->image_path)
            ? Storage::disk((string) config('media.disk', 'public'))->url($this->image_path)
            : null;
    }

    /**
     * @return HasMany<ProductPackageItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductPackageItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_package_category')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
