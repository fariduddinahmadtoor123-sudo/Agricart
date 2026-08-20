<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StoreHeroSlide extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'image_path',
        'alt_en',
        'alt_ur',
        'link',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    public function localizedAlt(): string
    {
        if (app()->getLocale() === 'ur' && filled($this->alt_ur)) {
            return (string) $this->alt_ur;
        }

        if (filled($this->alt_en)) {
            return (string) $this->alt_en;
        }

        return (string) ($this->alt_ur ?? '');
    }
}
