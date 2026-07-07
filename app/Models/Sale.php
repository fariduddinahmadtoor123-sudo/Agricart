<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const STATUS_HELD = 'held';

    public const STATUS_COMPLETED = 'completed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_HELD,
        self::STATUS_COMPLETED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'sale_number',
        'customer_id',
        'cashier_id',
        'sale_date',
        'locale',
        'payment_status',
        'payment_method',
        'bank_account_id',
        'price_display',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'print_controls',
        'notes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'print_controls' => 'boolean',
        ];
    }

    public function displayLocale(): string
    {
        return in_array($this->locale, ['en', 'ur'], true) ? $this->locale : 'en';
    }

    public function isHeld(): bool
    {
        return $this->status === self::STATUS_HELD;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class)->orderBy('sort_order');
    }
}
