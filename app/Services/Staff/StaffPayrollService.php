<?php

namespace App\Services\Staff;

use App\Models\StaffAdvanceTransaction;
use App\Models\StaffAttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StaffPayrollService
{
    public const ROLL_LIMIT = 500;

    public function __construct(
        private readonly StaffSalaryService $salaryService,
        private readonly StaffAdvanceService $advanceService,
    ) {}

    /**
     * @return array{from: Carbon, to: Carbon, days_in_month: int}
     */
    public function resolvePeriod(Carbon $month, ?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $from = $month->copy()->startOfMonth()->startOfDay();
        $monthEnd = $month->copy()->endOfMonth()->startOfDay();

        if ($month->isSameMonth($asOf) && $month->isSameYear($asOf)) {
            $to = $asOf->copy()->startOfDay();
        } else {
            $to = $monthEnd;
        }

        if ($to->lt($from)) {
            $to = $from->copy();
        }

        return [
            'from' => $from,
            'to' => $to,
            'days_in_month' => $month->daysInMonth,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summaryForUser(User $member, Carbon $month, ?Carbon $asOf = null): array
    {
        $period = $this->resolvePeriod($month, $asOf);
        $from = $period['from'];
        $to = $period['to'];
        $daysInMonth = $period['days_in_month'];

        $assignment = $this->salaryService->currentForUser($member, $to);
        $monthlyGross = $assignment !== null ? (float) $assignment->monthly_gross : 0.0;

        $records = StaffAttendanceRecord::query()
            ->where('user_id', $member->id)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('attendance_date')
            ->get();

        $paidDays = 0;
        $unpaidDays = 0;
        $leaveDays = 0;

        foreach ($records as $record) {
            if ($record->is_paid_day) {
                $paidDays++;
            }

            if ($record->status === StaffAttendanceRecord::STATUS_ABSENT_UNPAID) {
                $unpaidDays++;
            }

            if (in_array($record->status, [
                StaffAttendanceRecord::STATUS_ON_PAID_LEAVE,
                StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
            ], true)) {
                $leaveDays++;
            }
        }

        $earnedGross = $daysInMonth > 0
            ? round($monthlyGross * ($paidDays / $daysInMonth), 2)
            : 0.0;

        $advanceBalance = $this->advanceService->balanceForUser($member, $to);
        $owesUs = $advanceBalance > 0 ? $advanceBalance : 0.0;
        $weOwe = $advanceBalance < 0 ? abs($advanceBalance) : 0.0;

        $netPayable = round($earnedGross - $advanceBalance, 2);

        return [
            'user_id' => $member->id,
            'monthly_gross' => $monthlyGross,
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'days_in_month' => $daysInMonth,
            'paid_days' => $paidDays,
            'unpaid_days' => $unpaidDays,
            'leave_days' => $leaveDays,
            'earned_gross' => $earnedGross,
            'advance_balance' => $advanceBalance,
            'advance_owes_us' => $owesUs,
            'advance_we_owe' => $weOwe,
            'net_payable' => $netPayable,
            'has_salary' => $assignment !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rollForUser(User $member, Carbon $month, ?Carbon $asOf = null): array
    {
        $summary = $this->summaryForUser($member, $month, $asOf);
        $asOf ??= now();
        $period = $this->resolvePeriod($month, $asOf);

        $leaveRows = StaffAttendanceRecord::query()
            ->where('user_id', $member->id)
            ->whereBetween('attendance_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->whereIn('status', [
                StaffAttendanceRecord::STATUS_ON_PAID_LEAVE,
                StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
                StaffAttendanceRecord::STATUS_ABSENT_UNPAID,
            ])
            ->orderBy('attendance_date')
            ->get()
            ->map(fn (StaffAttendanceRecord $r): array => [
                'date' => $r->attendance_date->toDateString(),
                'status' => $r->status,
                'is_paid_day' => $r->is_paid_day,
                'notes' => $r->notes,
            ])
            ->all();

        $advanceRows = StaffAdvanceTransaction::query()
            ->where('user_id', $member->id)
            ->whereDate('transaction_date', '<=', $period['to']->toDateString())
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(fn (StaffAdvanceTransaction $t): array => [
                'date' => $t->transaction_date->toDateString(),
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'notes' => $t->notes,
            ])
            ->all();

        return [
            ...$summary,
            'member' => $member,
            'leave_rows' => $leaveRows,
            'advance_rows' => $advanceRows,
        ];
    }

    /**
     * @param  Collection<int, User>  $members
     * @return list<array<string, mixed>>
     */
    public function summariesForRoster(Collection $members, Carbon $month, ?Carbon $asOf = null): array
    {
        if ($members->isEmpty()) {
            return [];
        }

        $period = $this->resolvePeriod($month, $asOf);
        $ids = $members->pluck('id');
        $to = $period['to']->toDateString();
        $assignments = \App\Models\StaffSalaryAssignment::query()
            ->whereIn('user_id', $ids)
            ->whereDate('effective_from', '<=', $to)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $to))
            ->orderBy('user_id')
            ->orderByDesc('effective_from')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');
        $attendance = StaffAttendanceRecord::query()
            ->whereIn('user_id', $ids)
            ->whereBetween('attendance_date', [$period['from']->toDateString(), $to])
            ->get(['user_id', 'status', 'is_paid_day'])
            ->groupBy('user_id');
        $balances = $this->advanceService->balancesForRoster($ids, $period['to']);
        $summaries = [];

        foreach ($members as $member) {
            $assignment = $assignments->get($member->id);
            $records = $attendance->get($member->id, collect());
            $paidDays = $records->where('is_paid_day', true)->count();
            $unpaidDays = $records->where('status', StaffAttendanceRecord::STATUS_ABSENT_UNPAID)->count();
            $leaveDays = $records->whereIn('status', [
                StaffAttendanceRecord::STATUS_ON_PAID_LEAVE,
                StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
            ])->count();
            $monthlyGross = (float) ($assignment?->monthly_gross ?? 0);
            $earnedGross = $period['days_in_month'] > 0
                ? round($monthlyGross * ($paidDays / $period['days_in_month']), 2)
                : 0.0;
            $advanceBalance = (float) ($balances->get($member->id)['balance'] ?? 0);

            $summaries[$member->id] = [
                'user_id' => $member->id,
                'monthly_gross' => $monthlyGross,
                'period_from' => $period['from']->toDateString(),
                'period_to' => $to,
                'days_in_month' => $period['days_in_month'],
                'paid_days' => $paidDays,
                'unpaid_days' => $unpaidDays,
                'leave_days' => $leaveDays,
                'earned_gross' => $earnedGross,
                'advance_balance' => $advanceBalance,
                'advance_owes_us' => max(0, $advanceBalance),
                'advance_we_owe' => max(0, -$advanceBalance),
                'net_payable' => round($earnedGross - $advanceBalance, 2),
                'has_salary' => $assignment !== null,
            ];
        }

        return $summaries;
    }

    /**
     * @param  Collection<int, User>  $members
     * @return list<array<string, mixed>>
     */
    public function rollsForRoster(Collection $members, Carbon $month, ?Carbon $asOf = null): array
    {
        if ($members->isEmpty()) {
            return [];
        }

        $period = $this->resolvePeriod($month, $asOf);
        $ids = $members->pluck('id');
        $summaries = $this->summariesForRoster($members, $month, $asOf);
        $leaveRows = StaffAttendanceRecord::query()
            ->whereIn('user_id', $ids)
            ->whereBetween('attendance_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->whereIn('status', [
                StaffAttendanceRecord::STATUS_ON_PAID_LEAVE,
                StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
                StaffAttendanceRecord::STATUS_ABSENT_UNPAID,
            ])
            ->orderBy('attendance_date')
            ->get()
            ->groupBy('user_id');
        $advanceRows = StaffAdvanceTransaction::query()
            ->whereIn('user_id', $ids)
            ->whereDate('transaction_date', '<=', $period['to']->toDateString())
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        return $members->map(function (User $member) use ($summaries, $leaveRows, $advanceRows): array {
            return [
                ...$summaries[$member->id],
                'member' => $member,
                'leave_rows' => $leaveRows->get($member->id, collect())->map(fn (StaffAttendanceRecord $record): array => [
                    'date' => $record->attendance_date->toDateString(),
                    'status' => $record->status,
                    'is_paid_day' => $record->is_paid_day,
                    'notes' => $record->notes,
                ])->all(),
                'advance_rows' => $advanceRows->get($member->id, collect())->map(fn (StaffAdvanceTransaction $transaction): array => [
                    'date' => $transaction->transaction_date->toDateString(),
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'notes' => $transaction->notes,
                ])->all(),
            ];
        })->all();
    }

    public function formatMoney(float $amount): string
    {
        return number_format($amount, 0) . ' PKR';
    }
}
