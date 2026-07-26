<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyTestimonial extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_REJECTED = 'rejected';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_HIDDEN,
        self::STATUS_REJECTED,
    ];

    public const SOURCE_CUSTOMER = 'customer';

    public const SOURCE_ADMIN = 'admin';

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    public const MAX_BODY_LENGTH = 2000;

    public const MAX_TITLE_LENGTH = 160;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'display_name',
        'title',
        'rating',
        'body',
        'status',
        'is_featured',
        'source',
        'admin_reply',
        'admin_replied_by',
        'admin_replied_at',
        'moderated_by',
        'moderated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_featured' => 'boolean',
            'admin_replied_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_replied_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPublic(): bool
    {
        return $this->isApproved();
    }

    public function publicName(): string
    {
        return filled($this->display_name) ? $this->display_name : __('agricart.storefront.testimonials.anonymous');
    }

    public function publicPhotoUrl(): ?string
    {
        return $this->customer?->profilePhotoUrl();
    }
}
