<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTickerMessage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'text_en',
        'text_ur',
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

    public function localizedText(): string
    {
        if (app()->getLocale() === 'ur' && filled($this->text_ur)) {
            return $this->text_ur;
        }

        return $this->text_en;
    }
}
