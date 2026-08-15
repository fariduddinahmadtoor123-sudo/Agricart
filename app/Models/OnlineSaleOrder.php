<?php

namespace App\Models;

use App\Enums\ShippingChargeMode;
use Illuminate\Database\Eloquent\Builder;
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

    public const QUEUE_DOCUMENT_INVOICE = 'invoice';

    public const QUEUE_DOCUMENT_PACKING_LIST = 'packing_list';

    /** @var list<string> */
    public const QUEUE_DOCUMENTS = [
        self::QUEUE_DOCUMENT_INVOICE,
        self::QUEUE_DOCUMENT_PACKING_LIST,
    ];

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
        'shipping_amount',
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
        'queued_at',
        'queue_document',
        'converted_by_id',
        'notification_dispatched_at',
        'shipping_customer_notified_at',
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
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'cod_min_advance_percent' => 'decimal:2',
            'full_advance_required' => 'boolean',
            'shipping_charge_mode' => ShippingChargeMode::class,
            'converted_at' => 'datetime',
            'queued_at' => 'datetime',
            'notification_dispatched_at' => 'datetime',
            'shipping_customer_notified_at' => 'datetime',
            'payment_proof_reviewed_at' => 'datetime',
        ];
    }

    /**
     * Released for logistics handoff but not yet shipped (Pending list).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLogisticsPending(Builder $query): Builder
    {
        return $query
            ->where('order_status', self::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->where(function ($inner): void {
                $inner->whereNull('fulfillment_status')
                    ->orWhere('fulfillment_status', '!=', self::FULFILLMENT_DELIVERED);
            })
            ->where(function ($inner): void {
                $inner->whereHas(
                    'supplierAssignments',
                    fn ($assignment) => $assignment->whereNotNull('released_at')->whereNull('shipped_at'),
                )->orWhereHas(
                    'staffAssignments',
                    fn ($assignment) => $assignment->whereNotNull('released_at')->whereNull('shipped_at'),
                );
            });
    }

    /**
     * At least one parcel handed to carrier / bilty recorded (Shipped list).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLogisticsShipped(Builder $query): Builder
    {
        return $query
            ->where('order_status', self::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->where(function ($inner): void {
                $inner->whereHas(
                    'supplierAssignments',
                    fn ($assignment) => $assignment->whereNotNull('shipped_at'),
                )->orWhereHas(
                    'staffAssignments',
                    fn ($assignment) => $assignment->whereNotNull('shipped_at'),
                );
            });
    }

    /**
     * Dispatch queue: converted + queued, not yet forwarded to staff inbox.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeStillNeedsForward(Builder $query): Builder
    {
        return $query->whereDoesntHave(
            'staffAssignments',
            fn ($assignment) => $assignment->whereNotNull('sent_at'),
        );
    }

    public function isInDispatchQueue(): bool
    {
        return $this->order_status === self::STATUS_CONVERTED && $this->queued_at !== null;
    }

    public function isAwaitingDispatchQueue(): bool
    {
        return $this->order_status === self::STATUS_CONVERTED && $this->queued_at === null;
    }

    public function queueDocumentLabel(): string
    {
        $document = $this->queue_document;

        if (! in_array($document, self::QUEUE_DOCUMENTS, true)) {
            $document = self::QUEUE_DOCUMENT_INVOICE;
        }

        return __('online_sale_orders.queue_documents.'.$document);
    }

    /**
     * Customer-safe shipping rows (no supplier names).
     *
     * @return list<array{carrier: string|null, tracking: string|null, bilty: string|null}>
     */
    public function customerShippingParcels(): array
    {
        $this->loadMissing(['supplierAssignments', 'staffAssignments']);

        $parcels = [];

        foreach ($this->supplierAssignments as $assignment) {
            if ($assignment->status === OnlineSaleOrderSupplierAssignment::STATUS_DECLINED) {
                continue;
            }

            $parcel = $this->parcelFromAssignment(
                $assignment->carrier_name,
                $assignment->tracking_number,
                $assignment->shipping_reference,
            );

            if ($parcel !== null) {
                $parcels[] = $parcel;
            }
        }

        foreach ($this->staffAssignments as $assignment) {
            $parcel = $this->parcelFromAssignment(
                $assignment->carrier_name,
                $assignment->tracking_number,
                $assignment->shipping_reference,
            );

            if ($parcel !== null) {
                $parcels[] = $parcel;
            }
        }

        return $parcels;
    }

    /**
     * @return array{carrier: string|null, tracking: string|null, bilty: string|null}|null
     */
    private function parcelFromAssignment(mixed $carrierName, mixed $trackingNumber, mixed $shippingReference): ?array
    {
        $carrier = filled($carrierName) ? trim((string) $carrierName) : null;
        $tracking = filled($trackingNumber) ? trim((string) $trackingNumber) : null;
        $bilty = filled($shippingReference) ? trim((string) $shippingReference) : null;

        if ($carrier === null && $tracking === null && $bilty === null) {
            return null;
        }

        return [
            'carrier' => $carrier,
            'tracking' => $tracking,
            'bilty' => $bilty,
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

        if ($this->customer_payment_proof_status === self::PROOF_SUBMITTED) {
            return false;
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

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(OnlineSaleOrderStaffAssignment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnlineSaleOrderItem::class)->orderBy('sort_order');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(OnlineOrderReturnRequest::class);
    }
}
