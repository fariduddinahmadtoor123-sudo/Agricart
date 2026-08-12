<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

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
        'assigned_to_user_id',
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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function staffReads(): HasMany
    {
        return $this->hasMany(AgricartConversationRead::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgricartMessage::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(AgricartMessage::class)->latestOfMany();
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

    public function unreadForStaff(?User $user): bool
    {
        if (! $user instanceof User || $this->last_message_at === null || ! $this->isOpen()) {
            return false;
        }

        if (Schema::hasColumn('agricart_conversations', 'assigned_to_user_id')
            && $this->assigned_to_user_id !== null
            && (int) $this->assigned_to_user_id !== (int) $user->id) {
            return false;
        }

        $latest = $this->relationLoaded('latestMessage')
            ? $this->latestMessage
            : ($this->relationLoaded('messages')
                ? $this->messages->sortByDesc('id')->first()
                : $this->messages()->latest('id')->first());

        if (! $latest instanceof AgricartMessage || ! $latest->isFromCustomer()) {
            return false;
        }

        $read = null;

        if ($this->relationLoaded('staffReads')) {
            $read = $this->staffReads->firstWhere('user_id', $user->id);
        } else {
            $read = $this->staffReads()->where('user_id', $user->id)->first();
        }

        $staffUnread = $read === null || $read->last_read_at === null || $read->last_read_at->lt($this->last_message_at);
        $teamUnread = $this->admin_last_read_at === null || $this->admin_last_read_at->lt($this->last_message_at);

        return $staffUnread && $teamUnread;
    }

    public function teamHasReadMessage(AgricartMessage $message): bool
    {
        return $this->admin_last_read_at !== null
            && $this->admin_last_read_at->gte($message->created_at ?? $this->last_message_at);
    }

    public function customerHasReadMessage(AgricartMessage $message): bool
    {
        return $this->customer_last_read_at !== null
            && $this->customer_last_read_at->gte($message->created_at ?? $this->last_message_at);
    }

    public function latestAdminMessage(): ?AgricartMessage
    {
        $messages = $this->relationLoaded('messages')
            ? $this->messages
            : $this->messages()->get();

        $latest = $messages
            ->filter(fn (AgricartMessage $message): bool => $message->isFromAdmin())
            ->sortByDesc('id')
            ->first();

        return $latest instanceof AgricartMessage ? $latest : null;
    }

    public function latestMessageModel(): ?AgricartMessage
    {
        if ($this->relationLoaded('latestMessage')) {
            return $this->latestMessage;
        }

        if ($this->relationLoaded('messages')) {
            $latest = $this->messages->sortByDesc('id')->first();

            return $latest instanceof AgricartMessage ? $latest : null;
        }

        $latest = $this->messages()->latest('id')->first();

        return $latest instanceof AgricartMessage ? $latest : null;
    }

    /**
     * Staff-facing progress: read receipts + who must act next (not open/closed alone).
     *
     * @return 'closed'|'unread'|'reply_needed'|'sent_unread'|'read_waiting'|'notice'
     */
    public function queueState(?User $viewer = null): string
    {
        if (! $this->isOpen()) {
            return 'closed';
        }

        $latest = $this->latestMessageModel();

        if (! $latest instanceof AgricartMessage) {
            return 'notice';
        }

        if ($latest->isFromCustomer()) {
            $unread = $viewer instanceof User
                ? $this->unreadForStaff($viewer)
                : $this->unreadForAdmin();

            return $unread ? 'unread' : 'reply_needed';
        }

        if ($latest->isFromAdmin()) {
            return $this->customerHasReadMessage($latest) ? 'read_waiting' : 'sent_unread';
        }

        return $this->unreadForCustomer() ? 'sent_unread' : 'notice';
    }

    public function queueStateTone(?User $viewer = null): string
    {
        return match ($this->queueState($viewer)) {
            'closed' => 'closed',
            'unread' => 'alert',
            'reply_needed' => 'pending',
            'sent_unread' => 'waiting',
            'read_waiting' => 'read',
            default => 'notice',
        };
    }

    /**
     * @return 'closed'|'new_from_agricart'|'waiting_agricart'|'agricart_read'|'agricart_replied'|'notice'
     */
    public function customerQueueState(): string
    {
        if (! $this->isOpen()) {
            return 'closed';
        }

        if ($this->unreadForCustomer()) {
            return 'new_from_agricart';
        }

        $latest = $this->latestMessageModel();

        if (! $latest instanceof AgricartMessage) {
            return 'notice';
        }

        if ($latest->isFromCustomer()) {
            return $this->teamHasReadMessage($latest) ? 'agricart_read' : 'waiting_agricart';
        }

        if ($latest->isFromAdmin()) {
            return 'agricart_replied';
        }

        return 'notice';
    }
}
