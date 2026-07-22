<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OnlineSaleOrderStaffAssignment extends Model
{
    public const STATUS_SENT = 'sent';

    public const DOCUMENT_INVOICE = 'invoice';

    public const DOCUMENT_PACKING = 'packing';

    /** @var list<string> */
    protected $fillable = [
        'online_sale_order_id',
        'user_id',
        'document',
        'status',
        'sent_at',
        'released_at',
        'carrier_name',
        'tracking_number',
        'shipping_reference',
        'shipping_proof_path',
        'shipped_at',
        'assigned_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'released_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }

    public function isShipped(): bool
    {
        return $this->shipped_at !== null;
    }

    public function shippingProofUrl(): ?string
    {
        return filled($this->shipping_proof_path)
            ? Storage::disk('public')->url($this->shipping_proof_path)
            : null;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAwaitingRelease(Builder $query): Builder
    {
        return $query->whereNull('released_at')->where('status', self::STATUS_SENT);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrder::class, 'online_sale_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
