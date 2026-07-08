<?php

namespace App\Services\Staff;

use App\Models\StaffSalaryAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffSalaryService
{
    /**
     * Latest salary row per staff member (current or scheduled).
     *
     * @return Builder<StaffSalaryAssignment>
     */
    public function listQuery(): Builder
    {
        return StaffSalaryAssignment::query()
            ->with(['user.role'])
            ->where(function (Builder $query): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->whereIn('id', function ($sub): void {
                $sub->selectRaw('MAX(id)')
                    ->from('staff_salary_assignments')
                    ->groupBy('user_id');
            })
            ->latest('effective_from');
    }

    public function currentForUser(User $member): ?StaffSalaryAssignment
    {
        return StaffSalaryAssignment::query()
            ->where('user_id', $member->id)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function (Builder $query): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * @param  array{monthly_gross: string|float, effective_from: string, notes?: string|null, currency?: string}  $data
     */
    public function assign(User $member, array $data, ?User $actor = null): StaffSalaryAssignment
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.access.denied')],
            ]);
        }

        if (! in_array($member->status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true)) {
            throw ValidationException::withMessages([
                'user_id' => [__('agricart.staff.pay_management.messages.not_on_roster')],
            ]);
        }

        $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();
        $gross = round((float) $data['monthly_gross'], 2);

        if ($gross <= 0) {
            throw ValidationException::withMessages([
                'monthly_gross' => [__('agricart.staff.pay_management.messages.salary_positive')],
            ]);
        }

        return DB::transaction(function () use ($member, $data, $actor, $effectiveFrom, $gross): StaffSalaryAssignment {
            $previous = $this->currentForUser($member);

            if ($previous !== null && $previous->effective_from->gte($effectiveFrom)) {
                throw ValidationException::withMessages([
                    'effective_from' => [__('agricart.staff.pay_management.messages.effective_after_previous')],
                ]);
            }

            if ($previous !== null) {
                $previous->update([
                    'effective_to' => $effectiveFrom->copy()->subDay()->toDateString(),
                ]);
            }

            return StaffSalaryAssignment::query()->create([
                'user_id' => $member->id,
                'monthly_gross' => $gross,
                'currency' => $data['currency'] ?? 'PKR',
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => null,
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'created_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  array{monthly_gross: string|float, effective_from: string, notes?: string|null}  $data
     */
    public function updateAssignment(StaffSalaryAssignment $assignment, array $data, ?User $actor = null): StaffSalaryAssignment
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.access.denied')],
            ]);
        }

        $gross = round((float) $data['monthly_gross'], 2);

        if ($gross <= 0) {
            throw ValidationException::withMessages([
                'monthly_gross' => [__('agricart.staff.pay_management.messages.salary_positive')],
            ]);
        }

        $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();

        $assignment->update([
            'monthly_gross' => $gross,
            'effective_from' => $effectiveFrom->toDateString(),
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ]);

        return $assignment->fresh(['user.role']);
    }
}
