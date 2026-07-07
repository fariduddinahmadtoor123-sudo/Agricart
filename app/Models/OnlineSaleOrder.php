<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineSaleOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAYMENT_RECEIVED = 'payment_received';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAYMENT_RECEIVED,
        self::STATUS_PROCESSING,
        self::STATUS_CONVERTED,
        self::STATUS_CANCELLED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'order_number',
        'customer_id',
        'sale_id',
        'order_date',
        'locale',
        'order_status',
        'payment_status',
        'customer_payment_method',
        'customer_payment_note',
        'payment_method',
        'bank_account_id',
        'subtotal',
        'total_amount',
        'amount_paid',
        'ship_contact_name',
        'ship_phone',
        'ship_address',
        'ship_city',
        'customer_notes',
        'admin_notes',
        'converted_at',
        'converted_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'subtotal' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnlineSaleOrderItem::class)->orderBy('sort_order');
    }

    public function isLocked(): bool
    {
        return $this->order_status === self::STATUS_CONVERTED && $this->sale_id !== null;
    }
}
