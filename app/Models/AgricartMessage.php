<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgricartMessage extends Model
{
    public const SENDER_CUSTOMER = 'customer';

    public const SENDER_ADMIN = 'admin';

    public const SENDER_SYSTEM = 'system';

    /** @var list<string> */
    public const SENDER_TYPES = [
        self::SENDER_CUSTOMER,
        self::SENDER_ADMIN,
        self::SENDER_SYSTEM,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agricart_conversation_id',
        'sender_type',
        'customer_id',
        'user_id',
        'body',
        'is_system',
        'event_key',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgricartConversation::class, 'agricart_conversation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromCustomer(): bool
    {
        return $this->sender_type === self::SENDER_CUSTOMER;
    }

    public function isFromAdmin(): bool
    {
        return $this->sender_type === self::SENDER_ADMIN;
    }

    public function isSystem(): bool
    {
        return $this->is_system || $this->sender_type === self::SENDER_SYSTEM;
    }

    public function staffDisplayName(): string
    {
        $name = trim((string) ($this->user?->name ?? ''));

        return $name !== '' ? $name : __('agricart.inbox.sender.agricart');
    }
}
