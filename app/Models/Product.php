<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'slug',
        'status',
        'name_en',
        'name_ur',
        'category_id',
        'brand_id',
        'image_path',
        'gallery_images',
        'sku',
        'unit_id',
        'unit_qty',
        'pack_unit_id',
        'pack_qty',
        'required_qty',
        'alert_qty',
        'stock_qty',
        'retail_price',
        'wholesale_price',
        'super_wholesale_price',
        'distributor_price',
        'price_tier_quantities',
        'channel_prices',
        'short_description_en',
        'short_description_ur',
        'full_description_en',
        'full_description_ur',
        'usage_guidelines_en',
        'usage_guidelines_ur',
        'benefits_en',
        'benefits_ur',
        'warnings_en',
        'warnings_ur',
        'buying_guide_en',
        'buying_guide_ur',
        'seo_title_en',
        'seo_title_ur',
        'seo_description_en',
        'seo_description_ur',
        'seo_keywords_en',
        'seo_keywords_ur',
        'seo_focus_keyword_en',
        'seo_focus_keyword_ur',
        'search_terms',
        'search_document',
        'hs_code',
        'customs_notes_en',
        'customs_notes_ur',
        'import_notes_en',
        'import_notes_ur',
        'export_notes_en',
        'export_notes_ur',
        'label_code',
        'category_visual_map_code',
        'label_sequence',
        'label_qr',
        'label_barcode',
        'ai_status',
        'ai_generated_at',
        'ai_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'search_terms' => 'array',
            'gallery_images' => 'array',
            'channel_prices' => 'array',
            'price_tier_quantities' => 'array',
            'label_qr' => 'array',
            'label_barcode' => 'array',
            'ai_status' => CategoryAiStatus::class,
            'ai_generated_at' => 'datetime',
            'stock_qty' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $fields = [
                'name_en', 'name_ur', 'code', 'sku', 'label_code', 'slug',
                'short_description_en', 'short_description_ur',
                'full_description_en', 'full_description_ur',
                'usage_guidelines_en', 'usage_guidelines_ur',
                'benefits_en', 'benefits_ur', 'warnings_en', 'warnings_ur',
                'buying_guide_en', 'buying_guide_ur',
                'seo_title_en', 'seo_title_ur', 'seo_description_en', 'seo_description_ur',
                'seo_keywords_en', 'seo_keywords_ur', 'search_terms', 'hs_code',
            ];

            $product->search_document = trim(implode(' ', array_filter(array_map(
                static function (string $field) use ($product): string {
                    $value = $product->{$field};

                    return is_array($value)
                        ? implode(' ', array_filter(array_map('strval', $value)))
                        : strip_tags((string) $value);
                },
                $fields,
            ))));
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function packUnit(): BelongsTo
    {
        return $this->belongsTo(PackingUnit::class, 'pack_unit_id');
    }

    public function displayCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_display_category');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(CatalogAttribute::class, 'product_attribute', 'product_id', 'attribute_id')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function controls(): BelongsToMany
    {
        return $this->belongsToMany(CatalogControl::class, 'product_control', 'product_id', 'control_id')
            ->withTimestamps();
    }
}
