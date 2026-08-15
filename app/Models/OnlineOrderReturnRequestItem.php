<?php

namespace App\Models;

use App\Support\PublicStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineOrderReturnRequestItem extends Model
{
    public const REASON_DAMAGED = 'damaged';

    public const REASON_WRONG_ITEM = 'wrong_item';

    public const REASON_NOT_AS_DESCRIBED = 'not_as_described';

    public const REASON_CHANGED_MIND = 'changed_mind';

    public const REASON_OTHER = 'other';

    /** @var list<string> */
    public const REASONS = [
        self::REASON_DAMAGED,
        self::REASON_WRONG_ITEM,
        self::REASON_NOT_AS_DESCRIBED,
        self::REASON_CHANGED_MIND,
        self::REASON_OTHER,
    ];

    /** @var list<string> */
    protected $fillable = [
        'online_order_return_request_id',
        'online_sale_order_item_id',
        'product_id',
        'sort_order',
        'quantity',
        'reason',
        'photo_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(OnlineOrderReturnRequest::class, 'online_order_return_request_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrderItem::class, 'online_sale_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function photoUrl(): ?string
    {
        return PublicStorage::webUrl($this->photo_path);
    }
}
