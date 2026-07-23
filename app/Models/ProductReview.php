<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductReview extends Model
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

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    public const MAX_BODY_LENGTH = 2000;

    public const PATH_VERIFIED_PURCHASE = 'verified_purchase';

    public const PATH_PROOF = 'proof';

    /** @var list<string> */
    public const SUBMISSION_PATHS = [
        self::PATH_VERIFIED_PURCHASE,
        self::PATH_PROOF,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'customer_id',
        'online_sale_order_id',
        'online_sale_order_item_id',
        'submission_path',
        'rating',
        'body',
        'proof_note',
        'admin_reply',
        'admin_replied_by',
        'admin_replied_at',
        'status',
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
            'moderated_at' => 'datetime',
            'admin_replied_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrder::class, 'online_sale_order_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_replied_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProductReviewPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function galleryPhotos(): HasMany
    {
        return $this->photos()->where('kind', ProductReviewPhoto::KIND_GALLERY);
    }

    public function proofPhotos(): HasMany
    {
        return $this->photos()->where('kind', ProductReviewPhoto::KIND_PROOF);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ProductReviewReport::class);
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

    public function isVerifiedPurchase(): bool
    {
        return $this->submission_path === self::PATH_VERIFIED_PURCHASE;
    }

    public function isProofSubmission(): bool
    {
        return $this->submission_path === self::PATH_PROOF;
    }
}
