<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolsPendingPaymentSheet extends Model
{
    protected $table = 'tools_pending_payment_sheets';

    protected $fillable = [
        'user_id',
        'sheet_no',
        'sheet_date',
        'lang',
        'shop_name',
        'shop_address',
        'shop_phones',
        'prepared_by',
        'cells',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'shop_phones' => 'array',
            'cells' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
