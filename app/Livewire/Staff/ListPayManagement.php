<?php

namespace App\Livewire\Staff;

use App\Models\StaffSalaryAssignment;
use App\Models\User;
use App\Services\Staff\StaffMemberService;
use App\Services\Staff\StaffPermissions;
use App\Services\Staff\StaffSalaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPayManagement extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['user_number', 'name', 'monthly_gross', 'effective_from', 'status'];

    public string $search = '';

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('pay-management-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('pay-management-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('pay-management-column-toggle')]
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

        $this->dispatch('pay-management-columns-sync', columns: $this->visibleColumns);
    }

    public function openAssign(?int $userId = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.staff.pay_management.actions.assign'),
            resource: __('agricart.pages.staff.pay-management'),
            formKey: 'staff-salary',
            userId: $userId,
            mode: 'create',
        );
    }

    public function openEdit(int $userId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $member = User::query()->find($userId);

        if ($member === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.staff.pay_management.actions.edit', ['name' => $member->name]),
            resource: __('agricart.pages.staff.pay-management'),
            formKey: 'staff-salary',
            userId: $userId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $service = app(StaffSalaryService::class);

        $members = app(StaffMemberService::class)->rosterQuery()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle);
                });
            })
            ->orderBy('name')
            ->paginate(15);

        /** @var Collection<int, StaffSalaryAssignment> $salaryByUser */
        $salaryByUser = StaffSalaryAssignment::query()
            ->whereIn('user_id', $members->pluck('id'))
            ->where(function ($query): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->orderByDesc('effective_from')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        return view('livewire.staff.pay-management.list', [
            'members' => $members,
            'salaryByUser' => $salaryByUser,
            'canManage' => $this->canManage(),
        ]);
    }

    public function canManage(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return StaffPermissions::canManageRoster($user);
    }
}
