<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StoreBlogPost extends Model
{
    public const TYPE_ARTICLE = 'article';

    public const TYPE_VIDEO = 'video';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_ARTICLE,
        self::TYPE_VIDEO,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_en',
        'title_ur',
        'slug',
        'excerpt_en',
        'excerpt_ur',
        'body_en',
        'body_ur',
        'type',
        'youtube_url',
        'cover_image_path',
        'is_published',
        'published_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $inner): void {
                $inner->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function localizedTitle(): string
    {
        if (app()->getLocale() === 'ur' && filled($this->title_ur)) {
            return (string) $this->title_ur;
        }

        return (string) $this->title_en;
    }

    public function localizedExcerpt(): string
    {
        if (app()->getLocale() === 'ur' && filled($this->excerpt_ur)) {
            return (string) $this->excerpt_ur;
        }

        if (filled($this->excerpt_en)) {
            return (string) $this->excerpt_en;
        }

        return (string) ($this->excerpt_ur ?? '');
    }

    public function localizedBody(): string
    {
        if (app()->getLocale() === 'ur' && filled($this->body_ur)) {
            return (string) $this->body_ur;
        }

        if (filled($this->body_en)) {
            return (string) $this->body_en;
        }

        return (string) ($this->body_ur ?? '');
    }

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function coverImageUrl(): ?string
    {
        if (! filled($this->cover_image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image_path);
    }
}
