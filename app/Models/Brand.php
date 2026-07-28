<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'slug',
        'status',
        'inactive_reason_en',
        'inactive_reason_ur',
        'agricart_rating',
        'agricart_rating_criteria',
        'agricart_rating_reason_en',
        'agricart_rating_reason_ur',
        'agricart_rating_revision_en',
        'agricart_rating_revision_ur',
        'name_en',
        'name_ur',
        'image_path',
        'image_original_path',
        'image_watermark_version',
        'description_en',
        'description_ur',
        'website_url',
        'ai_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ai_status' => CategoryAiStatus::class,
            'agricart_rating' => 'integer',
            'agricart_rating_criteria' => 'array',
            'image_watermark_version' => 'integer',
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
