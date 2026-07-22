<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsVisitor extends Model
{
    protected $fillable = [
        'token_hash',
        'first_seen_at',
        'last_seen_at',
        'first_country_code',
        'first_country_name',
        'first_city',
        'first_region_name',
        'first_timezone',
        'first_device_type',
        'opted_out',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'opted_out' => 'boolean',
        ];
    }
}
