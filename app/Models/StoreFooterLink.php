<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreFooterLink extends Model
{
    public const GROUP_QUICK = 'quick_links';

    public const GROUP_REGISTRATION = 'registration';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'label_en',
        'label_ur',
        'url',
        'store_page_id',
        'category_id',
        'sort_order',
        'is_active',
        'opens_in_new_tab',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'opens_in_new_tab' => 'boolean',
        ];
    }

    public function storePage(): BelongsTo
    {
        return $this->belongsTo(StorePage::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function localizedLabel(): string
    {
        if (app()->getLocale() === 'ur' && filled($this->label_ur)) {
            return $this->label_ur;
        }

        return $this->label_en;
    }

    public function resolvedUrl(): string
    {
        if ($this->storePage !== null) {
            return $this->storePage->publicUrl();
        }

        if ($this->category !== null) {
            return route('store.category', $this->category->slug);
        }

        return $this->url ?: '#';
    }
}
