<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineSaleOrderSupplierAssignment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DECLINED = 'declined';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SENT,
        self::STATUS_ACKNOWLEDGED,
        self::STATUS_SHIPPED,
        self::STATUS_DECLINED,
    ];

    /** Statuses shown in admin fulfillment UI (WhatsApp "sent" is optional / legacy). */
    /** @var list<string> */
    public const ADMIN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACKNOWLEDGED,
        self::STATUS_SHIPPED,
        self::STATUS_DECLINED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'online_sale_order_id',
        'supplier_id',
        'status',
        'sent_at',
        'acknowledged_at',
        'shipped_at',
        'carrier_name',
        'tracking_number',
        'shipping_reference',
        'shipping_proof_path',
        'supplier_response_notes',
        'notes',
        'assigned_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public function shippingProofUrl(): ?string
    {
        return filled($this->shipping_proof_path)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->shipping_proof_path)
            : null;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrder::class, 'online_sale_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OnlineSaleOrderSupplierAssignmentItem::class, 'online_sale_order_supplier_assignment_id');
    }
}
