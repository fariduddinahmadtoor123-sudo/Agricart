<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'type',
    'amount',
    'transaction_date',
    'notes',
    'created_by',
])]
class StaffAdvanceTransaction extends Model
{
    public const TYPE_ADVANCE = 'advance';

    public const TYPE_REPAYMENT = 'repayment';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAdvance(): bool
    {
        return $this->type === self::TYPE_ADVANCE;
    }

    public function isRepayment(): bool
    {
        return $this->type === self::TYPE_REPAYMENT;
    }
}
