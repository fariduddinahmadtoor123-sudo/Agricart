<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgricartConversation extends Model
{
    public const TOPIC_GENERAL = 'general';

    public const TOPIC_ORDER = 'order';

    public const TOPIC_REVIEW = 'review';

    public const TOPIC_CONTACT = 'contact';

    /** @var list<string> */
    public const TOPICS = [
        self::TOPIC_GENERAL,
        self::TOPIC_ORDER,
        self::TOPIC_REVIEW,
        self::TOPIC_CONTACT,
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CLOSED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'subject',
        'topic',
        'status',
        'guest_name',
        'guest_phone',
        'guest_email',
        'online_sale_order_id',
        'product_review_id',
        'store_inquiry_id',
        'source_url',
        'last_message_at',
        'customer_last_read_at',
        'admin_last_read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'customer_last_read_at' => 'datetime',
            'admin_last_read_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrder::class, 'online_sale_order_id');
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(StoreInquiry::class, 'store_inquiry_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgricartMessage::class)->orderBy('id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function displayName(): string
    {
        if ($this->customer !== null) {
            return $this->customer->displayName();
        }

        return filled($this->guest_name) ? (string) $this->guest_name : __('agricart.inbox.guest');
    }

    public function unreadForCustomer(): bool
    {
        if ($this->last_message_at === null) {
            return false;
        }

        return $this->customer_last_read_at === null
            || $this->customer_last_read_at->lt($this->last_message_at);
    }

    public function unreadForAdmin(): bool
    {
        if ($this->last_message_at === null) {
            return false;
        }

        return $this->admin_last_read_at === null
            || $this->admin_last_read_at->lt($this->last_message_at);
    }
}
