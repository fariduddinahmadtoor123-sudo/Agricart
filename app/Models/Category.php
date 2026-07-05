<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'slug',
        'code',
        'status',
        'name_en',
        'name_ur',
        'image_path',
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
        'hs_code',
        'customs_notes_en',
        'customs_notes_ur',
        'import_notes_en',
        'import_notes_ur',
        'export_notes_en',
        'export_notes_ur',
        'visual_mapping',
        'faqs',
        'ai_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'search_terms' => 'array',
            'visual_mapping' => 'array',
            'faqs' => 'array',
            'ai_status' => CategoryAiStatus::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function displayInCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'category_display',
            'category_id',
            'display_category_id',
        )->withTimestamps();
    }

    public function displayedCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'category_display',
            'display_category_id',
            'category_id',
        )->withTimestamps();
    }
}
