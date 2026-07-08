<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'attendance_date',
    'status',
    'leave_type',
    'is_paid_day',
    'notes',
    'marked_by',
])]
class StaffAttendanceRecord extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ON_PAID_LEAVE = 'on_paid_leave';

    public const STATUS_ABSENT_UNPAID = 'absent_unpaid';

    public const STATUS_PUBLIC_HOLIDAY = 'public_holiday';

    public const LEAVE_MONTHLY = 'monthly_allowance';

    public const LEAVE_PUBLIC = 'public_holiday';

    public const LEAVE_UNPAID = 'unpaid';

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'is_paid_day' => 'boolean',
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
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_ON_PAID_LEAVE,
            self::STATUS_ABSENT_UNPAID,
            self::STATUS_PUBLIC_HOLIDAY,
        ];
    }
}
