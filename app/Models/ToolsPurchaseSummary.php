<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolsPurchaseSummary extends Model
{
    protected $table = 'tools_purchase_summaries';

    protected $fillable = [
        'user_id',
        'summary_no',
        'summary_date',
        'lang',
        'shop_name',
        'shop_address',
        'shop_phones',
        'prepared_by',
        'cells',
        'payment_sources',
        'pending_bills',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'shop_phones' => 'array',
            'cells' => 'array',
            'payment_sources' => 'array',
            'pending_bills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
