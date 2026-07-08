<?php

namespace App\Services\Staff;

use App\Models\StaffAttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffAttendanceService
{
    public function __construct(
        private readonly StaffLeaveService $leaveService,
    ) {}

    /**
     * @return Collection<int, StaffAttendanceRecord>
     */
    public function recordsForDate(Carbon $date): Collection
    {
        return StaffAttendanceRecord::query()
            ->with(['user.role'])
            ->whereDate('attendance_date', $date->toDateString())
            ->get()
            ->keyBy('user_id');
    }

    /**
     * @param  list<array{user_id: int, status: string, notes?: string|null}>  $rows
     */
    public function saveDaily(Carbon $date, array $rows, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.access.denied')],
            ]);
        }

        $publicHoliday = $this->leaveService->publicHolidayOn($date);
        $month = $date->copy()->startOfMonth();

        DB::transaction(function () use ($date, $rows, $actor, $publicHoliday, $month): void {
            $dateString = $date->toDateString();

            /** @var array<int, array{user_id: int, status: string, notes?: string|null}> $rowsByUser */
            $rowsByUser = [];

            foreach ($rows as $row) {
                $userId = (int) ($row['user_id'] ?? 0);

                if ($userId > 0) {
                    $rowsByUser[$userId] = $row;
                }
            }

            foreach ($rowsByUser as $row) {
                $userId = (int) ($row['user_id'] ?? 0);
                $inputStatus = (string) ($row['status'] ?? '');

                if ($userId <= 0 || $inputStatus === '') {
                    continue;
                }

                $member = User::query()->find($userId);

                if ($member === null || ! in_array($member->status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true)) {
                    continue;
                }

                [$status, $leaveType, $isPaid] = $this->resolveStatus(
                    $inputStatus,
                    $member,
                    $date,
                    $month,
                    $publicHoliday,
                );

                $values = [
                    'status' => $status,
                    'leave_type' => $leaveType,
                    'is_paid_day' => $isPaid,
                    'notes' => filled($row['notes'] ?? null) ? trim((string) $row['notes']) : null,
                    'marked_by' => $actor->id,
                ];

                $record = StaffAttendanceRecord::query()
                    ->where('user_id', $member->id)
                    ->whereDate('attendance_date', $dateString)
                    ->first();

                if ($record !== null) {
                    $record->update($values);

                    continue;
                }

                StaffAttendanceRecord::query()->create([
                    'user_id' => $member->id,
                    'attendance_date' => $dateString,
                    ...$values,
                ]);
            }
        });
    }

    /**
     * After midnight each roster member should start the day absent until marked present.
     * Creates missing records only; never overwrites HR marks.
     */
    public function ensureDefaultAbsencesForDate(Carbon $date): int
    {
        if ($date->isFuture()) {
            return 0;
        }

        $dateString = $date->toDateString();
        $publicHoliday = $this->leaveService->publicHolidayOn($date);
        $existingUserIds = StaffAttendanceRecord::query()
            ->whereDate('attendance_date', $dateString)
            ->pluck('user_id')
            ->all();

        $existingLookup = array_fill_keys($existingUserIds, true);
        $created = 0;

        $members = app(StaffMemberService::class)->rosterQuery()->get();

        foreach ($members as $member) {
            if (isset($existingLookup[$member->id])) {
                continue;
            }

            if ($publicHoliday !== null) {
                StaffAttendanceRecord::query()->create([
                    'user_id' => $member->id,
                    'attendance_date' => $dateString,
                    'status' => StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
                    'leave_type' => StaffAttendanceRecord::LEAVE_PUBLIC,
                    'is_paid_day' => $publicHoliday->is_paid,
                    'notes' => null,
                    'marked_by' => null,
                ]);
            } else {
                StaffAttendanceRecord::query()->create([
                    'user_id' => $member->id,
                    'attendance_date' => $dateString,
                    'status' => StaffAttendanceRecord::STATUS_ABSENT_UNPAID,
                    'leave_type' => StaffAttendanceRecord::LEAVE_UNPAID,
                    'is_paid_day' => false,
                    'notes' => null,
                    'marked_by' => null,
                ]);
            }

            $created++;
        }

        return $created;
    }

    /**
     * @return array{0: string, 1: string|null, 2: bool}
     */
    private function resolveStatus(
        string $inputStatus,
        User $member,
        Carbon $date,
        Carbon $month,
        ?\App\Models\StaffPublicHoliday $publicHoliday,
    ): array {
        if ($publicHoliday !== null && $inputStatus !== StaffAttendanceRecord::STATUS_PRESENT) {
            return [
                StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
                StaffAttendanceRecord::LEAVE_PUBLIC,
                $publicHoliday->is_paid,
            ];
        }

        if ($inputStatus === StaffAttendanceRecord::STATUS_PRESENT) {
            return [StaffAttendanceRecord::STATUS_PRESENT, null, true];
        }

        if ($inputStatus === StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY) {
            return [
                StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
                StaffAttendanceRecord::LEAVE_PUBLIC,
                true,
            ];
        }

        if ($inputStatus === 'absent' || $inputStatus === StaffAttendanceRecord::STATUS_ON_PAID_LEAVE) {
            $existing = StaffAttendanceRecord::query()
                ->where('user_id', $member->id)
                ->whereDate('attendance_date', $date->toDateString())
                ->first();

            $alreadyCounted = $existing?->status === StaffAttendanceRecord::STATUS_ON_PAID_LEAVE;
            $remaining = $this->leaveService->remainingPaidLeaves($member, $month);

            if ($alreadyCounted) {
                $remaining++;
            }

            if ($inputStatus === StaffAttendanceRecord::STATUS_ON_PAID_LEAVE
                || ($inputStatus === 'absent' && $remaining > 0)) {
                return [
                    StaffAttendanceRecord::STATUS_ON_PAID_LEAVE,
                    StaffAttendanceRecord::LEAVE_MONTHLY,
                    true,
                ];
            }

            return [
                StaffAttendanceRecord::STATUS_ABSENT_UNPAID,
                StaffAttendanceRecord::LEAVE_UNPAID,
                false,
            ];
        }

        if ($inputStatus === StaffAttendanceRecord::STATUS_ABSENT_UNPAID) {
            return [
                StaffAttendanceRecord::STATUS_ABSENT_UNPAID,
                StaffAttendanceRecord::LEAVE_UNPAID,
                false,
            ];
        }

        return [StaffAttendanceRecord::STATUS_PRESENT, null, true];
    }

    /**
     * @return array{present: int, absent: int, leave: int, unmarked: int, total: int}
     */
    public function summaryForDate(Carbon $date, Collection $rosterIds, Collection $records): array
    {
        $present = 0;
        $absent = 0;
        $leave = 0;

        foreach ($rosterIds as $userId) {
            $record = $records->get($userId);

            if ($record === null) {
                continue;
            }

            match ($record->status) {
                StaffAttendanceRecord::STATUS_PRESENT => $present++,
                StaffAttendanceRecord::STATUS_ON_PAID_LEAVE, StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY => $leave++,
                StaffAttendanceRecord::STATUS_ABSENT_UNPAID => $absent++,
                default => null,
            };
        }

        $total = $rosterIds->count();
        $marked = $present + $absent + $leave;

        return [
            'present' => $present,
            'absent' => $absent,
            'leave' => $leave,
            'unmarked' => max(0, $total - $marked),
            'total' => $total,
        ];
    }
}
