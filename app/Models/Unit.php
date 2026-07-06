<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'slug',
        'status',
        'name_en',
        'abbreviation_en',
        'name_ur',
        'abbreviation_ur',
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
