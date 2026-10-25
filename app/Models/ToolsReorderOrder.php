<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolsReorderOrder extends Model
{
    protected $table = 'tools_reorder_orders';

    protected $fillable = [
        'user_id',
        'order_no',
        'order_date',
        'lang',
        'shop_name',
        'shop_address',
        'shop_phones',
        'purchaser_name',
        'purchaser_index',
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
