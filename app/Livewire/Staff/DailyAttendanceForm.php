<?php

namespace App\Livewire\Staff;

use App\Models\StaffAttendanceRecord;
use App\Models\User;
use App\Services\Staff\StaffAttendanceService;
use App\Services\Staff\StaffLeaveService;
use App\Services\Staff\StaffMemberService;
use App\Services\Staff\StaffPermissions;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class DailyAttendanceForm extends Component
{
    public string $attendanceDate = '';

    /** @var list<array{user_id: int, status: string, notes: string}> */
    public array $rows = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('staff-attendance-form-open')]
    public function openForm(?string $attendanceDate = null): void
    {
        $this->attendanceDate = $attendanceDate ?: now()->format('Y-m-d');
        $this->statusMessage = null;
        $this->loadRows();
    }

    #[On('staff-attendance-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            default => null,
        };
    }

    public function updatedAttendanceDate(): void
    {
        $this->loadRows();
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('staff-attendance-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function render(): View
    {
        $date = Carbon::parse($this->attendanceDate);
        $leave = app(StaffLeaveService::class);
        $publicHoliday = $leave->publicHolidayOn($date);

        return view('livewire.staff.attendance.form', [
            'publicHoliday' => $publicHoliday,
        ]);
    }

    private function loadRows(): void
    {
        $date = Carbon::parse($this->attendanceDate);
        $attendance = app(StaffAttendanceService::class);

        if (! $date->isFuture()) {
            $attendance->ensureDefaultAbsencesForDate($date);
        }

        $leave = app(StaffLeaveService::class);
        $existing = $attendance->recordsForDate($date);
        $publicHoliday = $leave->publicHolidayOn($date);

        $members = app(StaffMemberService::class)->rosterQuery()
            ->orderByRaw("CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END", [User::STATUS_ACTIVE, User::STATUS_INACTIVE])
            ->orderBy('name')
            ->get();

        $this->rows = [];

        foreach ($members as $member) {
            $record = $existing->get($member->id);
            $defaultStatus = 'absent';

            if ($publicHoliday !== null && $record === null) {
                $defaultStatus = StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY;
            } elseif ($record === null && $date->isFuture()) {
                $defaultStatus = StaffAttendanceRecord::STATUS_PRESENT;
            }

            if ($record !== null) {
                $defaultStatus = match ($record->status) {
                    StaffAttendanceRecord::STATUS_ON_PAID_LEAVE => StaffAttendanceRecord::STATUS_ON_PAID_LEAVE,
                    StaffAttendanceRecord::STATUS_ABSENT_UNPAID => 'absent',
                    StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY => StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
                    default => StaffAttendanceRecord::STATUS_PRESENT,
                };
            }

            $remaining = $leave->remainingPaidLeaves($member, $date->copy()->startOfMonth());

            $this->rows[] = [
                'user_id' => $member->id,
                'name' => $member->name,
                'user_number' => (string) ($member->user_number ?? ''),
                'status' => $defaultStatus,
                'notes' => (string) ($record?->notes ?? ''),
                'remaining_leaves' => $remaining,
            ];
        }
    }

    private function persist(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! StaffPermissions::canMarkAttendance($user)) {
            $this->setStatus(__('agricart.access.denied'), 'danger');

            return false;
        }

        $this->validate([
            'attendanceDate' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.user_id' => ['required', 'integer'],
            'rows.*.status' => ['required', 'string'],
        ]);

        $this->saving = true;

        try {
            $payload = array_map(static fn (array $row): array => [
                'user_id' => (int) $row['user_id'],
                'status' => (string) $row['status'],
                'notes' => $row['notes'] ?? null,
            ], $this->rows);

            app(StaffAttendanceService::class)->saveDaily(
                Carbon::parse($this->attendanceDate),
                $payload,
                $user,
            );

            $this->setStatus(__('agricart.staff.attendance.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setStatus(collect($exception->errors())->flatten()->first() ?: __('agricart.staff.attendance.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatch('staff-attendance-form-busy', saving: $this->saving);
        }
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
