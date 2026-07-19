<?php

namespace App\Livewire\Staff;

use App\Models\User;
use App\Services\Staff\StaffMemberService;
use App\Services\Staff\StaffPayrollService;
use App\Services\Staff\StaffPermissions;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListStaffPayroll extends Component
{
    use WithPagination;

    public string $payrollMonth = '';

    /** @var list<string> */
    public array $visibleColumns = [
        'user_number',
        'name',
        'monthly_gross',
        'paid_days',
        'earned_gross',
        'advance_net',
        'net_payable',
    ];

    public string $search = '';

    public function mount(): void
    {
        if ($this->payrollMonth === '') {
            $this->payrollMonth = now()->format('Y-m');
        }

        $this->dispatch('staff-payroll-columns-sync', columns: $this->visibleColumns);
        $this->syncToolbar();
    }

    #[On('staff-payroll-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('staff-payroll-set-month')]
    public function setMonthFromToolbar(string $month): void
    {
        if ($month === '') {
            return;
        }

        $this->payrollMonth = Carbon::parse($month . '-01')->format('Y-m');
        $this->syncToolbar();
    }

    #[On('staff-payroll-column-toggle')]
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
            $this->visibleColumns = ['name', 'net_payable'];
        }

        $this->dispatch('staff-payroll-columns-sync', columns: $this->visibleColumns);
    }

    public function openGenerateRoll(): void
    {
        if (! $this->canRun()) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.staff.payroll.actions.generate_roll'),
            resource: __('agricart.pages.staff.payroll'),
            formKey: 'staff-payroll',
            payrollMonth: $this->payrollMonth,
            mode: 'create',
        );
    }

    public function render(): View
    {
        $month = Carbon::parse($this->payrollMonth . '-01');
        $payroll = app(StaffPayrollService::class);

        $members = app(StaffMemberService::class)->rosterQuery()
            ->when(! $this->canViewAll(), function ($query): void {
                $user = auth()->user();
                if ($user instanceof User) {
                    $query->where('id', $user->id);
                }
            })
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle)
                        ->orWhere('email', 'like', $needle);
                });
            })
            ->orderBy('name')
            ->paginate(50);

        $rows = $payroll->summariesForRoster($members->getCollection(), $month);

        return view('livewire.staff.payroll.list', [
            'members' => $members,
            'rows' => $rows,
            'visibleColumns' => $this->visibleColumns,
            'money' => fn (float $amount): string => $payroll->formatMoney($amount),
        ]);
    }

    public function canRun(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canRunPayroll($user);
    }

    private function canViewAll(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canViewAllPayroll($user);
    }

    private function syncToolbar(): void
    {
        $this->dispatch('staff-payroll-toolbar-sync', payrollMonth: $this->payrollMonth);
    }
}
