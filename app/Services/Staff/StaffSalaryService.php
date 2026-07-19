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

    public function currentForUser(User $member, Carbon|string|null $asOf = null): ?StaffSalaryAssignment
    {
        $date = $asOf instanceof Carbon
            ? $asOf->toDateString()
            : ($asOf !== null ? Carbon::parse($asOf)->toDateString() : now()->toDateString());

        return StaffSalaryAssignment::query()
            ->where('user_id', $member->id)
            ->where('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
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
            User::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();
            $timeline = StaffSalaryAssignment::query()
                ->where('user_id', $member->id)
                ->orderBy('effective_from')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $futureExists = $timeline->contains(
                fn (StaffSalaryAssignment $row): bool => $row->effective_from->gte($effectiveFrom),
            );

            if ($futureExists) {
                throw ValidationException::withMessages([
                    'effective_from' => [__('agricart.staff.pay_management.messages.effective_after_previous')],
                ]);
            }

            $previous = $timeline->last();
            if ($previous !== null) {
                $previous->update([
                    'effective_to' => $effectiveFrom->copy()->subDay()->toDateString(),
                ]);
            }

            $created = StaffSalaryAssignment::query()->create([
                'user_id' => $member->id,
                'monthly_gross' => $gross,
                'currency' => $data['currency'] ?? 'PKR',
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => null,
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'created_by' => $actor->id,
            ]);

            $this->assertValidTimeline(
                StaffSalaryAssignment::query()->where('user_id', $member->id)->orderBy('effective_from')->get(),
            );

            return $created;
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

        return DB::transaction(function () use ($assignment, $gross, $effectiveFrom, $data): StaffSalaryAssignment {
            User::query()->whereKey($assignment->user_id)->lockForUpdate()->firstOrFail();
            $timeline = StaffSalaryAssignment::query()
                ->where('user_id', $assignment->user_id)
                ->orderBy('effective_from')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $locked = $timeline->firstWhere('id', $assignment->id);

            if (! $locked instanceof StaffSalaryAssignment) {
                abort(404);
            }

            $locked->forceFill([
                'monthly_gross' => $gross,
                'effective_from' => $effectiveFrom->toDateString(),
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            ]);
            $this->assertValidTimeline($timeline);
            $locked->save();

            return $locked->fresh(['user.role']);
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, StaffSalaryAssignment>  $timeline
     */
    private function assertValidTimeline(\Illuminate\Support\Collection $timeline): void
    {
        $previousEnd = null;
        $index = 0;

        foreach ($timeline->sortBy(fn (StaffSalaryAssignment $row): string => $row->effective_from->format('Y-m-d').str_pad((string) $row->id, 20, '0', STR_PAD_LEFT))->values() as $row) {
            $from = $row->effective_from->copy()->startOfDay();
            $to = $row->effective_to?->copy()->startOfDay();

            if ($to !== null && $to->lt($from)) {
                throw ValidationException::withMessages([
                    'effective_from' => [__('agricart.staff.pay_management.messages.effective_after_previous')],
                ]);
            }

            if ($index > 0 && $previousEnd === null) {
                throw ValidationException::withMessages([
                    'effective_from' => [__('agricart.staff.pay_management.messages.effective_after_previous')],
                ]);
            }

            if ($previousEnd instanceof Carbon && $from->lte($previousEnd)) {
                throw ValidationException::withMessages([
                    'effective_from' => [__('agricart.staff.pay_management.messages.effective_after_previous')],
                ]);
            }

            $previousEnd = $to;
            $index++;
        }
    }
}
