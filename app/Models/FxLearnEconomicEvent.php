<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FxLearnEconomicEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'fingerprint',
        'external_id',
        'title',
        'ccy',
        'country',
        'impact',
        'at_ms',
        'event_date',
        'previous',
        'forecast',
        'actual',
        'unit',
        'source',
        'released_at',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'at_ms' => 'integer',
            'event_date' => 'date',
            'released_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
