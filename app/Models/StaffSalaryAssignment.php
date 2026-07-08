<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'monthly_gross',
    'currency',
    'effective_from',
    'effective_to',
    'notes',
    'created_by',
])]
class StaffSalaryAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'monthly_gross' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
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

    public function isCurrent(): bool
    {
        if ($this->effective_to !== null && $this->effective_to->isPast()) {
            return false;
        }

        return $this->effective_from->lte(now()->startOfDay());
    }
}
