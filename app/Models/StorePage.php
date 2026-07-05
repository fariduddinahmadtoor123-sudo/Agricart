<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StorePage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_en',
        'title_ur',
        'slug',
        'content_en',
        'content_ur',
        'is_active',
        'show_in_menu',
        'show_in_footer',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'show_in_footer' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function localizedTitle(): string
    {
        if (app()->getLocale() === 'ur' && filled($this->title_ur)) {
            return $this->title_ur;
        }

        return $this->title_en;
    }

    public function localizedContent(): ?string
    {
        if (app()->getLocale() === 'ur' && filled($this->content_ur)) {
            return $this->content_ur;
        }

        $content = $this->content_en;

        return filled($content) ? $content : null;
    }

    public function publicUrl(): string
    {
        return route('store.page', $this->slug);
    }

    public static function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = Str::slug($base);
        $original = $slug;
        $counter = 2;

        while (static::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
