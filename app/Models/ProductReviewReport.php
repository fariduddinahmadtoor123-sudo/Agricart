<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReviewReport extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    public const REASON_SPAM = 'spam';

    public const REASON_OFFENSIVE = 'offensive';

    public const REASON_WRONG_PRODUCT = 'wrong_product';

    public const REASON_OTHER = 'other';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_RESOLVED,
        self::STATUS_DISMISSED,
    ];

    /** @var list<string> */
    public const REASONS = [
        self::REASON_SPAM,
        self::REASON_OFFENSIVE,
        self::REASON_WRONG_PRODUCT,
        self::REASON_OTHER,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_review_id',
        'customer_id',
        'reason',
        'note',
        'status',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
