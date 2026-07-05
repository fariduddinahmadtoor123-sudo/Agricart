<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreMenuItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'label_en',
        'label_ur',
        'url',
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
        if ($this->category !== null) {
            return route('store.category', $this->category->slug);
        }

        return $this->url ?: '#';
    }
}
