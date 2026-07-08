<?php

namespace App\Services\Staff;

use App\Models\StaffAdvanceTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffAdvanceService
{
    /**
     * Positive = staff owes company. Negative = company owes staff.
     */
    public function balanceForUser(User $member): float
    {
        $advances = (float) StaffAdvanceTransaction::query()
            ->where('user_id', $member->id)
            ->where('type', StaffAdvanceTransaction::TYPE_ADVANCE)
            ->sum('amount');

        $repayments = (float) StaffAdvanceTransaction::query()
            ->where('user_id', $member->id)
            ->where('type', StaffAdvanceTransaction::TYPE_REPAYMENT)
            ->sum('amount');

        return round($advances - $repayments, 2);
    }

    /**
     * @return Collection<int, array{user_id: int, balance: float, owes_us: float, we_owe: float}>
     */
    public function balancesForRoster(Collection $userIds): Collection
    {
        if ($userIds->isEmpty()) {
            return collect();
        }

        $rows = StaffAdvanceTransaction::query()
            ->select('user_id')
            ->selectRaw("SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as advances_total", [StaffAdvanceTransaction::TYPE_ADVANCE])
            ->selectRaw("SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as repayments_total", [StaffAdvanceTransaction::TYPE_REPAYMENT])
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->get();

        return $rows->mapWithKeys(function ($row): array {
            $balance = round((float) $row->advances_total - (float) $row->repayments_total, 2);

            return [
                (int) $row->user_id => [
                    'user_id' => (int) $row->user_id,
                    'balance' => $balance,
                    'owes_us' => $balance > 0 ? $balance : 0.0,
                    'we_owe' => $balance < 0 ? abs($balance) : 0.0,
                ],
            ];
        });
    }

    /**
     * @return Builder<StaffAdvanceTransaction>
     */
    public function historyQuery(?int $restrictUserId = null): Builder
    {
        return StaffAdvanceTransaction::query()
            ->with(['user.role', 'createdBy'])
            ->when($restrictUserId !== null, fn (Builder $q) => $q->where('user_id', $restrictUserId))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }

    /**
     * @param  array{type: string, amount: float|string, transaction_date: string, notes?: string|null}  $payload
     */
    public function record(User $member, array $payload, ?User $actor = null): StaffAdvanceTransaction
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.access.denied')],
            ]);
        }

        if (! in_array($member->status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true)) {
            throw ValidationException::withMessages([
                'user_id' => [__('agricart.staff.advances.messages.not_on_roster')],
            ]);
        }

        $type = (string) ($payload['type'] ?? '');
        $amount = round((float) ($payload['amount'] ?? 0), 2);

        if (! in_array($type, [StaffAdvanceTransaction::TYPE_ADVANCE, StaffAdvanceTransaction::TYPE_REPAYMENT], true)) {
            throw ValidationException::withMessages([
                'type' => [__('agricart.staff.advances.messages.invalid_type')],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.staff.advances.messages.amount_positive')],
            ]);
        }

        $date = Carbon::parse((string) ($payload['transaction_date'] ?? ''));

        return DB::transaction(function () use ($member, $type, $amount, $date, $payload, $actor): StaffAdvanceTransaction {
            return StaffAdvanceTransaction::query()->create([
                'user_id' => $member->id,
                'type' => $type,
                'amount' => $amount,
                'transaction_date' => $date->toDateString(),
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                'created_by' => $actor->id,
            ]);
        });
    }

    public function formatMoney(float $amount): string
    {
        return number_format($amount, 0) . ' PKR';
    }
}
