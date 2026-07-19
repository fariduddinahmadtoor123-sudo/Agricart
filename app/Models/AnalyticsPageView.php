<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsPageView extends Model
{
    protected $fillable = [
        'visitor_id',
        'session_id',
        'occurred_at',
        'route_name',
        'page_type',
        'entity_id',
        'entity_slug',
        'response_status',
        'duration_ms',
        'referrer_host',
        'traffic_source',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'response_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}
