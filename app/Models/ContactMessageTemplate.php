<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactMessageTemplate extends Model
{
    public const AUDIENCE_CUSTOMER = 'customer';

    public const AUDIENCE_SUPPLIER = 'supplier';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name_en',
        'name_ur',
        'subject_en',
        'subject_ur',
        'body_en',
        'body_ur',
        'audience',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForAudience(Builder $query, string $audience): Builder
    {
        return $query->where('audience', $audience);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'ur' && filled($this->name_ur)) {
            return (string) $this->name_ur;
        }

        if (filled($this->name_en)) {
            return (string) $this->name_en;
        }

        return (string) ($this->name_ur ?: '');
    }

    public function localizedSubject(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'ur' && filled($this->subject_ur)) {
            return (string) $this->subject_ur;
        }

        if (filled($this->subject_en)) {
            return (string) $this->subject_en;
        }

        return (string) ($this->subject_ur ?: '');
    }

    public function localizedBody(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'ur' && filled($this->body_ur)) {
            return (string) $this->body_ur;
        }

        if (filled($this->body_en)) {
            return (string) $this->body_en;
        }

        return (string) ($this->body_ur ?: '');
    }
}
