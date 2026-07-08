<?php

namespace App\Services\Staff;

use App\Filament\Clusters\Staff\AdvancesPage;
use App\Filament\Clusters\Staff\AttendancePage;
use App\Filament\Clusters\Staff\LeaveManagementPage;
use App\Filament\Clusters\Staff\PayManagementPage;
use App\Filament\Clusters\Staff\PayrollPage;
use App\Filament\Clusters\Staff\StaffPage;
use App\Models\StaffAdvanceTransaction;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffPublicHoliday;
use App\Models\User;

class StaffOverviewService
{
    public function __construct(
        private readonly StaffMemberService $members,
        private readonly StaffSalaryService $salaries,
        private readonly StaffAdvanceService $advances,
    ) {}

    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        $cards = [
            $this->membersCard(),
            $this->payManagementCard(),
            $this->attendanceCard(),
            $this->advancesCard(),
            $this->leaveCard(),
            $this->payrollCard(),
        ];

        $visible = array_filter($cards, function (array $card): bool {
            $pageClass = $card['page'];

            return $pageClass::canAccess();
        });

        return array_values(array_map(function (array $card): array {
            unset($card['page']);

            return $card;
        }, $visible));
    }

    public function totalRecords(): int
    {
        return array_sum(array_column($this->statCards(), 'count'));
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function membersCard(): array
    {
        $query = $this->members->rosterQuery();
        $count = (clone $query)->count();
        $active = (clone $query)->where('status', User::STATUS_ACTIVE)->count();

        return $this->card('members', $count, $active, StaffPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function payManagementCard(): array
    {
        $rosterCount = $this->members->rosterQuery()->count();
        $assigned = $this->salaries->listQuery()->count();

        return $this->card('pay_management', $rosterCount, $assigned, PayManagementPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function attendanceCard(): array
    {
        $today = now()->toDateString();
        $markedToday = StaffAttendanceRecord::query()->whereDate('attendance_date', $today);
        $count = (clone $markedToday)->count();
        $present = (clone $markedToday)
            ->where('status', StaffAttendanceRecord::STATUS_PRESENT)
            ->count();

        return $this->card('attendance', $count, $present, AttendancePage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function advancesCard(): array
    {
        $rosterIds = $this->members->rosterQuery()->pluck('id');
        $withBalance = $this->advances->balancesForRoster($rosterIds)
            ->filter(fn (array $row): bool => abs($row['balance']) > 0.001)
            ->count();

        $transactionCount = StaffAdvanceTransaction::query()
            ->whereIn('user_id', $rosterIds)
            ->count();

        return $this->card('advances', $transactionCount, $withBalance, AdvancesPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function leaveCard(): array
    {
        $year = (int) now()->year;
        $holidays = StaffPublicHoliday::query()->whereYear('holiday_date', $year);
        $count = (clone $holidays)->count();
        $upcoming = (clone $holidays)
            ->whereDate('holiday_date', '>=', now()->toDateString())
            ->count();

        return $this->card('leave', $count, $upcoming, LeaveManagementPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function payrollCard(): array
    {
        $rosterCount = $this->members->rosterQuery()->count();
        $withSalary = $this->salaries->listQuery()->count();

        return $this->card('payroll', $rosterCount, $withSalary, PayrollPage::class);
    }

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function card(string $key, int $count, ?int $active, string $pageClass): array
    {
        return [
            'key' => $key,
            'label' => (string) __('agricart.staff.overview.cards.' . $key),
            'count' => $count,
            'active' => $active,
            'url' => $pageClass::getUrl(),
            'page' => $pageClass,
        ];
    }
}
