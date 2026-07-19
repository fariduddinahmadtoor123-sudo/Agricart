<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsRollup extends Model
{
    protected $fillable = [
        'grain',
        'period_start',
        'dimension_type',
        'dimension_key',
        'dimension_label',
        'page_views',
        'visitors',
        'sessions',
        'new_visitors',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'page_views' => 'integer',
            'visitors' => 'integer',
            'sessions' => 'integer',
            'new_visitors' => 'integer',
        ];
    }
}
