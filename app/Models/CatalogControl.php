<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Model;

class CatalogControl extends Model
{
    protected $table = 'controls';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'slug',
        'status',
        'name_en',
        'name_ur',
        'value_en',
        'value_ur',
        'ai_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ai_status' => CategoryAiStatus::class,
        ];
    }
}
