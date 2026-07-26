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
        'show_in_legal',
        'show_in_quick_links',
        'show_in_registration',
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
            'show_in_legal' => 'boolean',
            'show_in_quick_links' => 'boolean',
            'show_in_registration' => 'boolean',
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

    /**
     * @return list<string>
     */
    public function placementKeys(): array
    {
        $placements = [];

        if ($this->show_in_menu) {
            $placements[] = 'menu';
        }

        if ($this->show_in_quick_links) {
            $placements[] = 'quick_links';
        }

        if ($this->show_in_footer) {
            $placements[] = 'policies';
        }

        if ($this->show_in_legal) {
            $placements[] = 'legal';
        }

        if ($this->show_in_registration) {
            $placements[] = 'registration';
        }

        return $placements;
    }

    public function hasAnyPlacement(): bool
    {
        return $this->show_in_menu
            || $this->show_in_quick_links
            || $this->show_in_footer
            || $this->show_in_legal
            || $this->show_in_registration;
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
