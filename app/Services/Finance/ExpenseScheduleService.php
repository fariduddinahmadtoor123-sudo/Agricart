<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\ExpenseBillCycle;
use App\Models\ExpensePayment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ExpenseScheduleService
{
    /**
     * @return array{year: int, month: int, issue_on: Carbon, due_on: Carbon|null, next_issue_on: Carbon, window_start: Carbon}|null
     */
    public function cycleForMonth(Expense $expense, int $year, int $month): ?array
    {
        if ($expense->issue_day === null) {
            return null;
        }

        $issueOn = $this->dateInMonth($year, $month, (int) $expense->issue_day);
        $next = $issueOn->copy()->addMonthNoOverflow()->startOfMonth();
        $nextIssueOn = $this->dateInMonth((int) $next->year, (int) $next->month, (int) $expense->issue_day);
        $dueOn = $this->dueOnForIssue($expense, $issueOn);

        return [
            'year' => $year,
            'month' => $month,
            'issue_on' => $issueOn,
            'due_on' => $dueOn,
            'next_issue_on' => $nextIssueOn,
            'window_start' => $issueOn->copy()->startOfMonth(),
        ];
    }

    /**
     * @return list<array{year: int, month: int, issue_on: Carbon, due_on: Carbon|null, next_issue_on: Carbon, window_start: Carbon}>
     */
    public function openCycles(Expense $expense, ?CarbonInterface $today = null): array
    {
        $today = Carbon::parse($today ?? now())->startOfDay();
        $open = [];

        foreach ($this->issuedCycles($expense, $today) as $cycle) {
            if (! $this->isCycleComplete($expense, $cycle)) {
                $open[] = $cycle;
            }
        }

        return $open;
    }

    /**
     * @return array{year: int, month: int, issue_on: Carbon, due_on: Carbon|null, next_issue_on: Carbon, window_start: Carbon}|null
     */
    public function oldestOpenCycle(Expense $expense, ?CarbonInterface $today = null): ?array
    {
        $open = $this->openCycles($expense, $today);

        return $open[0] ?? null;
    }

    public function dueStatus(Expense $expense, ?CarbonInterface $today = null): ?string
    {
        if ($expense->issue_day === null) {
            return null;
        }

        $today = Carbon::parse($today ?? now())->startOfDay();
        $open = $this->oldestOpenCycle($expense, $today);

        if ($open !== null) {
            $due = $open['due_on'];

            if ($due !== null && $due->lt($today)) {
                return Expense::DUE_OVERDUE;
            }

            if ($due !== null && $due->lte($today->copy()->addDays(3))) {
                return Expense::DUE_SOON;
            }

            return Expense::DUE_ISSUED;
        }

        $thisMonth = $this->cycleForMonth($expense, (int) $today->year, (int) $today->month);

        if ($thisMonth !== null && $thisMonth['issue_on']->gt($today)) {
            return Expense::DUE_UPCOMING;
        }

        return Expense::DUE_PAID;
    }

    /**
     * @return list<int>
     */
    public function awaitingExpenseIds(?CarbonInterface $today = null): array
    {
        return $this->awaitingExpenses($today)->pluck('id')->all();
    }

    public function openCycleCount(?CarbonInterface $today = null): int
    {
        $today = Carbon::parse($today ?? now())->startOfDay();
        $count = 0;

        foreach ($this->scheduledExpenses() as $expense) {
            $count += count($this->openCycles($expense, $today));
        }

        return $count;
    }

    /**
     * @return Collection<int, Expense>
     */
    public function awaitingExpenses(?CarbonInterface $today = null): Collection
    {
        $today = Carbon::parse($today ?? now())->startOfDay();

        return $this->scheduledExpenses()
            ->filter(fn (Expense $expense): bool => $this->openCycles($expense, $today) !== [])
            ->values();
    }

    /**
     * @param  list<string>  $statuses
     * @return list<int>
     */
    public function expenseIdsWithDueStatus(array $statuses, ?CarbonInterface $today = null): array
    {
        $today = Carbon::parse($today ?? now())->startOfDay();
        $ids = [];

        foreach ($this->scheduledExpenses() as $expense) {
            $status = $this->dueStatus($expense, $today);

            if ($status !== null && in_array($status, $statuses, true)) {
                $ids[] = $expense->id;
            }
        }

        return $ids;
    }

    public function markOldestOpenDone(Expense $expense, ?User $actor = null): ExpenseBillCycle
    {
        $cycle = $this->oldestOpenCycle($expense);

        if ($cycle === null) {
            throw ValidationException::withMessages([
                'expense' => [__('agricart.finance.expenses.messages.no_open_cycle')],
            ]);
        }

        return $this->completeCycle($expense, $cycle, ExpenseBillCycle::VIA_DONE, $actor);
    }

    public function completeFromPayment(ExpensePayment $payment): void
    {
        $expense = $payment->expense ?? Expense::query()->find($payment->expense_id);

        if (! $expense instanceof Expense || $expense->issue_day === null || $payment->payment_date === null) {
            return;
        }

        $paidOn = $payment->payment_date->copy()->startOfDay();

        foreach ($this->issuedCycles($expense, $paidOn) as $cycle) {
            if ($paidOn->lt($cycle['window_start']) || $paidOn->gte($cycle['next_issue_on'])) {
                continue;
            }

            if ($this->isCycleComplete($expense, $cycle)) {
                return;
            }

            $this->completeCycle($expense, $cycle, ExpenseBillCycle::VIA_PAID, $payment->createdBy);

            return;
        }
    }

    /**
     * @param  array{year: int, month: int, issue_on: Carbon, due_on: Carbon|null, next_issue_on: Carbon, window_start: Carbon}  $cycle
     */
    public function isCycleComplete(Expense $expense, array $cycle): bool
    {
        $marked = $expense->relationLoaded('billCycles')
            ? $expense->billCycles
            : (Schema::hasTable('expense_bill_cycles') ? $expense->billCycles()->get() : collect());

        $done = $marked->first(function (ExpenseBillCycle $row) use ($cycle): bool {
            return (int) $row->cycle_year === $cycle['year']
                && (int) $row->cycle_month === $cycle['month']
                && $row->isCompleted();
        });

        if ($done !== null) {
            return true;
        }

        $payments = $expense->relationLoaded('payments')
            ? $expense->payments
            : $expense->payments()->get();

        return $payments->contains(function (ExpensePayment $payment) use ($cycle): bool {
            if ($payment->payment_date === null) {
                return false;
            }

            $paidOn = $payment->payment_date->copy()->startOfDay();

            return $paidOn->gte($cycle['window_start']) && $paidOn->lt($cycle['next_issue_on']);
        });
    }

    /**
     * @return list<array{year: int, month: int, issue_on: Carbon, due_on: Carbon|null, next_issue_on: Carbon, window_start: Carbon}>
     */
    private function issuedCycles(Expense $expense, Carbon $today): array
    {
        if ($expense->issue_day === null) {
            return [];
        }

        $start = ($expense->created_at ?? $today)->copy()->startOfMonth();
        $cursor = $start->copy();
        $cycles = [];
        $guard = 0;

        while ($guard < 24) {
            $cycle = $this->cycleForMonth($expense, (int) $cursor->year, (int) $cursor->month);

            if ($cycle === null || $cycle['issue_on']->gt($today)) {
                break;
            }

            $cycles[] = $cycle;
            $cursor->addMonthNoOverflow();
            $guard++;
        }

        return $cycles;
    }

    /**
     * @param  array{year: int, month: int, issue_on: Carbon, due_on: Carbon|null, next_issue_on: Carbon, window_start: Carbon}  $cycle
     */
    private function completeCycle(Expense $expense, array $cycle, string $via, ?User $actor = null): ExpenseBillCycle
    {
        $row = ExpenseBillCycle::query()->firstOrNew([
            'expense_id' => $expense->id,
            'cycle_year' => $cycle['year'],
            'cycle_month' => $cycle['month'],
        ]);

        $row->issue_on = $cycle['issue_on']->toDateString();
        $row->due_on = $cycle['due_on']?->toDateString();
        $row->completed_at = now();
        $row->completed_via = $via;
        $row->completed_by = $actor?->id;
        $row->save();

        return $row;
    }

    private function dueOnForIssue(Expense $expense, Carbon $issueOn): ?Carbon
    {
        if ($expense->due_day === null) {
            return null;
        }

        $dueDay = (int) $expense->due_day;

        if ($dueDay >= (int) $expense->issue_day) {
            return $this->dateInMonth((int) $issueOn->year, (int) $issueOn->month, $dueDay);
        }

        $next = $issueOn->copy()->addMonthNoOverflow()->startOfMonth();

        return $this->dateInMonth((int) $next->year, (int) $next->month, $dueDay);
    }

    private function dateInMonth(int $year, int $month, int $day): Carbon
    {
        $last = Carbon::create($year, $month, 1)->daysInMonth;

        return Carbon::create($year, $month, min(max($day, 1), $last))->startOfDay();
    }

    public function estimatedOccurrences(Expense $expense, CarbonInterface|string $from, CarbonInterface|string $to): int
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();

        if ($to->lt($from)) {
            return 0;
        }

        if ($expense->created_at !== null) {
            $created = Carbon::parse($expense->created_at)->startOfDay();

            if ($created->gt($from)) {
                $from = $created;
            }
        }

        if ($to->lt($from)) {
            return 0;
        }

        if ($expense->issue_day !== null) {
            $count = 0;
            $cursor = $from->copy()->startOfMonth();
            $guard = 0;

            while ($cursor->lte($to) && $guard < 120) {
                $cycle = $this->cycleForMonth($expense, (int) $cursor->year, (int) $cursor->month);

                if ($cycle !== null && $cycle['issue_on']->betweenIncluded($from, $to)) {
                    $count++;
                }

                $cursor->addMonthNoOverflow();
                $guard++;
            }

            return $count;
        }

        $count = 0;
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();

        while ($cursor->lte($end) && $count < 120) {
            $count++;
            $cursor->addMonthNoOverflow();
        }

        return $count;
    }

    /**
     * @param  Collection<int, Expense>|iterable<int, Expense>  $expenses
     */
    public function estimatedTotalForPeriod(iterable $expenses, CarbonInterface|string $from, CarbonInterface|string $to): float
    {
        $total = 0.0;

        foreach ($expenses as $expense) {
            if (! $expense instanceof Expense) {
                continue;
            }

            $amount = (float) ($expense->estimated_cost ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $total += $amount * $this->estimatedOccurrences($expense, $from, $to);
        }

        return round($total, 2);
    }

    /**
     * @return Collection<int, Expense>
     */
    private function scheduledExpenses(): Collection
    {
        if (! Schema::hasColumn('expenses', 'issue_day')) {
            return collect();
        }

        $query = Expense::query()
            ->where('status', Expense::STATUS_ACTIVE)
            ->whereNotNull('issue_day')
            ->with(['payments']);

        if (Schema::hasTable('expense_bill_cycles')) {
            $query->with('billCycles');
        }

        return $query->get();
    }
}
