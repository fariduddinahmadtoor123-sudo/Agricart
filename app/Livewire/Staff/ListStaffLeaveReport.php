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

class ListStaffLeaveReport extends Component
{
    public const REPORT_DAY_COUNT = 10;

    public string $focusDate = '';

    public string $search = '';

    public ?string $statusFilter = null;

    /** @var list<string> */
    public array $visibleColumns = ['user_number', 'name', 'role', 'attendance_status', 'paid_day'];

    public function mount(): void
    {
        if ($this->focusDate === '') {
            $this->focusDate = now()->format('Y-m-d');
        }

        $this->syncToolbar();
        $this->dispatch('staff-leave-columns-sync', columns: $this->visibleColumns);
    }

    #[On('staff-leave-settings-saved')]
    public function refreshReport(): void
    {
        $this->syncToolbar();
    }

    #[On('staff-leave-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('staff-leave-filter')]
    public function applyFilter(string $filter): void
    {
        $this->statusFilter = ($filter === 'all' || $filter === '') ? null : $filter;
    }

    #[On('staff-leave-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = null;
    }

    #[On('staff-leave-column-toggle')]
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

        $this->dispatch('staff-leave-columns-sync', columns: $this->visibleColumns);
    }

    #[On('staff-leave-set-date')]
    public function setDateFromToolbar(string $date): void
    {
        if ($date === '') {
            return;
        }

        $this->focusDate = Carbon::parse($date)->format('Y-m-d');
        $this->syncToolbar();
    }

    public function selectDay(string $date): void
    {
        if ($date === '') {
            return;
        }

        $this->focusDate = Carbon::parse($date)->format('Y-m-d');
        $this->syncToolbar();
    }

    public function render(): View
    {
        $anchor = Carbon::parse($this->focusDate);
        $attendance = app(StaffAttendanceService::class);
        $leaveService = app(StaffLeaveService::class);

        $rosterQuery = app(StaffMemberService::class)->rosterQuery();

        if (! $this->canViewAll()) {
            $user = auth()->user();
            if ($user instanceof User) {
                $rosterQuery->where('id', $user->id);
            }
        }

        $rosterIds = $rosterQuery->pluck('id');

        $daySummaries = $this->buildDaySummaries($anchor, $rosterIds, $attendance, $leaveService);

        $detailDate = $anchor->copy();
        if (! $detailDate->isFuture()) {
            $attendance->ensureDefaultAbsencesForDate($detailDate);
        }

        $records = $attendance->recordsForDate($detailDate);
        $publicHoliday = $leaveService->publicHolidayOn($detailDate);

        $members = $rosterQuery
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle)
                        ->orWhere('email', 'like', $needle);
                });
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END', [User::STATUS_ACTIVE, User::STATUS_INACTIVE])
            ->orderBy('name')
            ->get();

        $members = $this->applyLeaveOnlyFilter($members, $records);
        $members = $this->applyStatusFilter($members, $records);

        return view('livewire.staff.leave.report', [
            'daySummaries' => $daySummaries,
            'members' => $members,
            'records' => $records,
            'publicHoliday' => $publicHoliday,
            'detailDate' => $detailDate,
            'visibleColumns' => $this->visibleColumns,
        ]);
    }

    public function canManageSettings(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canManageLeaveSettings($user);
    }

    private function canViewAll(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canViewAllLeave($user);
    }

    private function syncToolbar(): void
    {
        $this->dispatch('staff-leave-toolbar-sync', focusDate: $this->focusDate);
    }

    /**
     * @param  Collection<int, int|string>  $rosterIds
     * @return list<array{date: string, label: string, summary: array{present: int, absent: int, leave: int, unmarked: int, total: int}, public_holiday: ?\App\Models\StaffPublicHoliday, is_selected: bool}>
     */
    private function buildDaySummaries(
        Carbon $anchor,
        Collection $rosterIds,
        StaffAttendanceService $attendance,
        StaffLeaveService $leaveService,
    ): array {
        $rows = [];

        for ($offset = 0; $offset < self::REPORT_DAY_COUNT; $offset++) {
            $date = $anchor->copy()->subDays($offset);

            if (! $date->isFuture()) {
                $attendance->ensureDefaultAbsencesForDate($date);
            }

            $records = $attendance->recordsForDate($date);
            $summary = $attendance->summaryForDate($date, $rosterIds, $records);
            $holiday = $leaveService->publicHolidayOn($date);
            $dateString = $date->toDateString();

            $rows[] = [
                'date' => $dateString,
                'label' => $date->translatedFormat('D, j M Y'),
                'summary' => $summary,
                'public_holiday' => $holiday,
                'is_selected' => $dateString === $anchor->toDateString(),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, User>  $members
     * @param  Collection<int, StaffAttendanceRecord>  $records
     * @return Collection<int, User>
     */
    private function applyLeaveOnlyFilter(Collection $members, Collection $records): Collection
    {
        return $members->filter(function (User $member) use ($records): bool {
            $status = $records->get($member->id)?->status ?? 'unmarked';

            return in_array($status, [
                StaffAttendanceRecord::STATUS_ON_PAID_LEAVE,
                StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY,
                StaffAttendanceRecord::STATUS_ABSENT_UNPAID,
            ], true);
        })->values();
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
