<?php

namespace App\Livewire\Staff;

use App\Models\StaffAdvanceTransaction;
use App\Models\User;
use App\Services\Staff\StaffAdvanceService;
use App\Services\Staff\StaffMemberService;
use App\Services\Staff\StaffPermissions;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ListStaffAdvances extends Component
{
    /** @var list<string> */
    public array $visibleColumns = ['user_number', 'name', 'balance_side', 'balance_amount'];

    public string $search = '';

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('staff-advances-saved')]
    public function refreshList(): void
    {
        // balances refresh on re-render
    }

    #[On('staff-advances-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('staff-advances-filter')]
    public function applyFilter(string $filter): void
    {
        // balance list only; filters reserved for reports history
    }

    #[On('staff-advances-clear-filters')]
    public function clearFilters(): void
    {
        //
    }

    #[On('staff-advances-column-toggle')]
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
            $this->visibleColumns = ['name', 'balance_amount'];
        }

        $this->dispatch('staff-advances-columns-sync', columns: $this->visibleColumns);
    }

    public function mount(): void
    {
        $this->dispatch('staff-advances-columns-sync', columns: $this->visibleColumns);
    }

    public function render(): View
    {
        $service = app(StaffAdvanceService::class);
        $restrictUserId = $this->restrictedUserId();

        $members = app(StaffMemberService::class)->rosterQuery()
            ->when($restrictUserId !== null, fn ($q) => $q->where('id', $restrictUserId))
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle);
                });
            })
            ->orderBy('name')
            ->get();

        $balances = $service->balancesForRoster($members->pluck('id'));

        return view('livewire.staff.advances.list', [
            'members' => $members,
            'balances' => $balances,
            'canManage' => $this->canManage(),
            'money' => fn (float $amount): string => $service->formatMoney($amount),
            'visibleColumns' => $this->visibleColumns,
        ]);
    }

    public function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canManageAdvances($user);
    }

    private function restrictedUserId(): ?int
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        if (StaffPermissions::canViewAllAdvances($user)) {
            return null;
        }

        return $user->id;
    }
}
