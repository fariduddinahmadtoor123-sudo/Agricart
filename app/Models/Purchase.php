<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Purchase extends Model
{
    public const DELIVERY_PENDING = 'pending';

    public const DELIVERY_PARTIAL = 'partial';

    public const DELIVERY_RECEIVED = 'received';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_PAID = 'paid';

    public const DISPUTE_OK = 'ok';

    public const DISPUTE_DISPUTED = 'disputed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'purchaser_id',
        'locale',
        'purchase_date',
        'delivery_status',
        'payment_status',
        'dispute_status',
        'supplier_balance_snapshot',
        'subtotal',
        'amount_paid',
        'total_amount',
        'balance_due',
        'vendor_bill_path',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'supplier_balance_snapshot' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchaser_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class)->orderBy('sort_order');
    }

    public function displayLocale(): string
    {
        return in_array($this->locale, ['en', 'ur'], true) ? $this->locale : 'en';
    }

    public function vendorBillUrl(): ?string
    {
        return filled($this->vendor_bill_path)
            ? Storage::disk('public')->url($this->vendor_bill_path)
            : null;
    }
}
