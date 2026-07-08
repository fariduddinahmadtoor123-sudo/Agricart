<?php

namespace App\Services\Staff;

use App\Models\StaffAttendanceRecord;
use App\Models\StaffLeaveSetting;
use App\Models\StaffPublicHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffLeaveService
{
    public function monthlyPaidLeaveAllowance(): int
    {
        return (int) StaffLeaveSetting::current()->monthly_paid_leaves;
    }

    public function updateMonthlyAllowance(int $count): StaffLeaveSetting
    {
        if ($count < 0 || $count > 31) {
            throw ValidationException::withMessages([
                'monthly_paid_leaves' => [__('agricart.staff.leave.messages.allowance_range')],
            ]);
        }

        $setting = StaffLeaveSetting::current();
        $setting->update(['monthly_paid_leaves' => $count]);

        return $setting;
    }

    public function paidLeavesUsedInMonth(User $member, Carbon $month): int
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        return (int) StaffAttendanceRecord::query()
            ->where('user_id', $member->id)
            ->where('status', StaffAttendanceRecord::STATUS_ON_PAID_LEAVE)
            ->whereBetween('attendance_date', [$start, $end])
            ->count();
    }

    public function remainingPaidLeaves(User $member, Carbon $month): int
    {
        return max(0, $this->monthlyPaidLeaveAllowance() - $this->paidLeavesUsedInMonth($member, $month));
    }

    public function publicHolidayOn(Carbon $date): ?StaffPublicHoliday
    {
        return StaffPublicHoliday::query()
            ->whereDate('holiday_date', $date->toDateString())
            ->first();
    }

    /**
     * @param  array{holiday_date: string, title_en: string, title_ur?: string|null, is_paid?: bool, notes?: string|null}  $data
     */
    public function addPublicHoliday(array $data, ?User $actor = null): StaffPublicHoliday
    {
        $actor ??= auth()->user();

        return StaffPublicHoliday::query()->create([
            'holiday_date' => $data['holiday_date'],
            'title_en' => $data['title_en'],
            'title_ur' => filled($data['title_ur'] ?? null) ? $data['title_ur'] : null,
            'is_paid' => ($data['is_paid'] ?? true) === true,
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            'created_by' => $actor instanceof User ? $actor->id : null,
        ]);
    }

    public function deletePublicHoliday(StaffPublicHoliday $holiday): void
    {
        $holiday->delete();
    }

    /**
     * @param  list<array{holiday_date: string, title_en: string, title_ur?: string|null, is_paid?: bool, notes?: string|null}>  $rows
     */
    public function addPublicHolidays(array $rows, ?User $actor = null): int
    {
        $created = 0;

        foreach ($rows as $row) {
            $title = trim((string) ($row['title_en'] ?? ''));

            if ($title === '' || blank($row['holiday_date'] ?? null)) {
                continue;
            }

            $this->addPublicHoliday([
                'holiday_date' => (string) $row['holiday_date'],
                'title_en' => $title,
                'title_ur' => $row['title_ur'] ?? null,
                'is_paid' => ($row['is_paid'] ?? true) === true,
                'notes' => $row['notes'] ?? null,
            ], $actor);

            $created++;
        }

        return $created;
    }
}
