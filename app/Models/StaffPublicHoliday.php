<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'holiday_date',
    'title_en',
    'title_ur',
    'is_paid',
    'notes',
    'created_by',
])]
class StaffPublicHoliday extends Model
{
    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_paid' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
