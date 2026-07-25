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
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class ListDailyAttendance extends Component
{
    public string $attendanceDate = '';

    public string $search = '';

    /** @var list<string> */
    public array $visibleColumns = ['user_number', 'name', 'role', 'attendance_status', 'paid_day'];

    public ?string $statusFilter = null;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        if ($this->attendanceDate === '') {
            $this->attendanceDate = now()->format('Y-m-d');
        }

        $this->syncToolbar();
        $this->dispatch('staff-attendance-columns-sync', columns: $this->visibleColumns);
    }

    #[On('staff-attendance-saved')]
    public function refreshList(): void
    {
        $this->syncToolbar();
    }

    #[On('staff-attendance-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('staff-attendance-filter')]
    public function applyFilter(string $filter): void
    {
        $this->statusFilter = ($filter === 'all' || $filter === '') ? null : $filter;
    }

    #[On('staff-attendance-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = null;
    }

    #[On('staff-attendance-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $col): bool => $col !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['name'];
        }

        $this->dispatch('staff-attendance-columns-sync', columns: $this->visibleColumns);
    }

    #[On('staff-attendance-set-date')]
    public function setDateFromToolbar(string $date): void
    {
        if ($date === '') {
            return;
        }

        $this->attendanceDate = Carbon::parse($date)->format('Y-m-d');
        $this->syncToolbar();
    }

    public function updatedAttendanceDate(): void
    {
        $this->syncToolbar();
    }

    public function openMarkAttendance(): void
    {
        if (! $this->canMark()) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.staff.attendance.actions.mark_daily'),
            resource: __('agricart.pages.staff.attendance'),
            formKey: 'staff-attendance',
            attendanceDate: $this->attendanceDate,
            mode: 'create',
        );
    }

    public function render(): View
    {
        $date = Carbon::parse($this->attendanceDate);
        $attendance = app(StaffAttendanceService::class);
        $leave = app(StaffLeaveService::class);
        $records = $attendance->recordsForDate($date);
        $publicHoliday = $leave->publicHolidayOn($date);

        $members = app(StaffMemberService::class)->rosterQuery()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle)
                        ->orWhere('email', 'like', $needle);
                });
            })
            ->orderByRaw("CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END", [User::STATUS_ACTIVE, User::STATUS_INACTIVE])
            ->orderBy('name')
            ->get();

        $members = $this->applyStatusFilter($members, $records);

        return view('livewire.staff.attendance.list', [
            'members' => $members,
            'records' => $records,
            'publicHoliday' => $publicHoliday,
        ]);
    }

    public function canMark(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canMarkAttendance($user);
    }

    private function syncToolbar(): void
    {
        $this->dispatch(
            'staff-attendance-toolbar-sync',
            attendanceDate: $this->attendanceDate,
        );
    }

    /**
     * @param  Collection<int, User>  $members
     * @param  Collection<int, StaffAttendanceRecord>  $records
     * @return Collection<int, User>
     */
    private function applyStatusFilter(Collection $members, Collection $records): Collection
    {
        if ($this->statusFilter === null || ! str_starts_with($this->statusFilter, 'status:')) {
            return $members;
        }

        $status = substr($this->statusFilter, strlen('status:'));

        return $members->filter(function (User $member) use ($records, $status): bool {
            $recordStatus = $records->get($member->id)?->status ?? 'unmarked';

            return $recordStatus === $status;
        })->values();
    }
}
