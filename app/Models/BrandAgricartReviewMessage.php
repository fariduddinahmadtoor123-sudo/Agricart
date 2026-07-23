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
        'high_en',
        'high_ur',
        'low_en',
        'low_ur',
    ];
}
