<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSession extends Model
{
    protected $fillable = [
        'visitor_id',
        'token_hash',
        'started_at',
        'last_seen_at',
        'landing_route',
        'country_code',
        'country_name',
        'city',
        'region_name',
        'region_code',
        'timezone',
        'latitude',
        'longitude',
        'masked_ip',
        'ip_hash',
        'device_type',
        'browser',
        'operating_system',
        'referrer_host',
        'traffic_source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'page_views',
        'conversions',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'page_views' => 'integer',
            'conversions' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
