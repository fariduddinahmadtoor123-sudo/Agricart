<?php

namespace App\Models;

use App\Enums\ShippingChargeMode;
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

    public const PROOF_NONE = 'none';

    public const PROOF_AWAITING = 'awaiting_receipt';

    public const PROOF_SUBMITTED = 'submitted';

    public const PROOF_VERIFIED = 'verified';

    public const PROOF_REJECTED = 'rejected';

    /** @var list<string> */
    public const PAYMENT_PROOF_STATUSES = [
        self::PROOF_NONE,
        self::PROOF_AWAITING,
        self::PROOF_SUBMITTED,
        self::PROOF_VERIFIED,
        self::PROOF_REJECTED,
    ];

    public const FULFILLMENT_AWAITING = 'awaiting_assignment';

    public const FULFILLMENT_ASSIGNED = 'assigned';

    public const FULFILLMENT_SOURCING = 'sourcing';

    public const FULFILLMENT_PICKING = 'picking_packing';

    public const FULFILLMENT_READY = 'ready_to_ship';

    public const FULFILLMENT_TRANSIT = 'in_transit';

    public const FULFILLMENT_OUT = 'out_for_delivery';

    public const FULFILLMENT_DELIVERED = 'delivered';

    public const FULFILLMENT_ON_HOLD = 'on_hold';

    /** @var list<string> */
    public const FULFILLMENT_STATUSES = [
        self::FULFILLMENT_AWAITING,
        self::FULFILLMENT_ASSIGNED,
        self::FULFILLMENT_SOURCING,
        self::FULFILLMENT_PICKING,
        self::FULFILLMENT_READY,
        self::FULFILLMENT_TRANSIT,
        self::FULFILLMENT_OUT,
        self::FULFILLMENT_DELIVERED,
        self::FULFILLMENT_ON_HOLD,
    ];

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
        'checkout_token',
        'customer_id',
        'sale_id',
        'order_date',
        'locale',
        'order_status',
        'fulfillment_status',
        'payment_status',
        'customer_payment_method',
        'customer_payment_note',
        'customer_payment_reference',
        'customer_bank_receipt_path',
        'customer_payment_proof_status',
        'payment_proof_rejection_note',
        'payment_proof_reviewed_at',
        'payment_proof_reviewed_by_id',
        'payment_method',
        'bank_account_id',
        'subtotal',
        'total_amount',
        'amount_paid',
        'ship_contact_name',
        'ship_phone',
        'ship_address',
        'ship_city',
        'delivery_method_id',
        'delivery_method_name_en',
        'delivery_method_name_ur',
        'shipping_charge_mode',
        'cod_min_advance_percent',
        'full_advance_required',
        'delivery_customer_notice_en',
        'delivery_customer_notice_ur',
        'customer_notes',
        'admin_notes',
        'converted_at',
        'converted_by_id',
        'notification_dispatched_at',
        'assigned_user_id',
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
            'cod_min_advance_percent' => 'decimal:2',
            'full_advance_required' => 'boolean',
            'shipping_charge_mode' => ShippingChargeMode::class,
            'converted_at' => 'datetime',
            'notification_dispatched_at' => 'datetime',
            'payment_proof_reviewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    public function localizedDeliveryMethodName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $name = $locale === 'ur' && filled($this->delivery_method_name_ur)
            ? $this->delivery_method_name_ur
            : $this->delivery_method_name_en;

        return filled($name) ? (string) $name : null;
    }

    public function localizedDeliveryNotice(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $notice = $locale === 'ur' && filled($this->delivery_customer_notice_ur)
            ? $this->delivery_customer_notice_ur
            : $this->delivery_customer_notice_en;

        return filled($notice) ? (string) $notice : null;
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function paymentProofReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_proof_reviewed_by_id');
    }

    public function customerBankReceiptUrl(): ?string
    {
        return $this->adminPaymentProofUrl();
    }

    public function customerPaymentProofUrl(): ?string
    {
        return filled($this->customer_bank_receipt_path)
            ? route('store.customer.orders.payment-proof.show', $this->order_number)
            : null;
    }

    public function adminPaymentProofUrl(): ?string
    {
        return filled($this->customer_bank_receipt_path)
            ? route('admin.online-sale-orders.payment-proof.show', $this)
            : null;
    }

    public function requiresCustomerPaymentProof(): bool
    {
        if ((string) $this->customer_payment_method !== 'bank_transfer') {
            return false;
        }

        if ($this->order_status === self::STATUS_CANCELLED) {
            return false;
        }

        if ($this->customer_payment_proof_status === self::PROOF_VERIFIED) {
            return false;
        }

        if ($this->customer_payment_proof_status === self::PROOF_SUBMITTED
            && ! filled($this->customer_bank_receipt_path)) {
            return true;
        }

        return in_array($this->customer_payment_proof_status, [
            self::PROOF_AWAITING,
            self::PROOF_REJECTED,
        ], true);
    }

    public function customerBankReceiptIsImage(): bool
    {
        if (! filled($this->customer_bank_receipt_path)) {
            return false;
        }

        return (bool) preg_match('/\.(jpe?g|png|webp)$/i', (string) $this->customer_bank_receipt_path);
    }

    public function needsBankReceiptUpload(): bool
    {
        return $this->requiresCustomerPaymentProof();
    }

    public function isLocked(): bool
    {
        return $this->order_status === self::STATUS_CONVERTED && $this->sale_id !== null;
    }

    public function supplierAssignments(): HasMany
    {
        return $this->hasMany(OnlineSaleOrderSupplierAssignment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnlineSaleOrderItem::class)->orderBy('sort_order');
    }
}
