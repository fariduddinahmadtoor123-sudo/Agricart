<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandAgricartReviewMessage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'criterion_key',
        'positive_en',
        'positive_ur',
        'neutral_en',
        'neutral_ur',
        'needs_improvement_en',
        'needs_improvement_ur',
    ];
}
