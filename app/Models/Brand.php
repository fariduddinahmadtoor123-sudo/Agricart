<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'slug',
        'status',
        'inactive_reason_en',
        'inactive_reason_ur',
        'agricart_rating',
        'name_en',
        'name_ur',
        'image_path',
        'description_en',
        'description_ur',
        'website_url',
        'ai_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ai_status' => CategoryAiStatus::class,
            'agricart_rating' => 'integer',
        ];
    }
}
