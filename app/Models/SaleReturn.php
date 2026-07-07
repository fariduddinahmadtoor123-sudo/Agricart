<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'return_number',
        'sale_id',
        'customer_id',
        'processed_by_id',
        'return_date',
        'refund_subtotal',
        'refund_total',
        'payment_status',
        'payment_method',
        'bank_account_id',
        'amount_paid',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'refund_subtotal' => 'decimal:2',
            'refund_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class)->orderBy('sort_order');
    }
}
