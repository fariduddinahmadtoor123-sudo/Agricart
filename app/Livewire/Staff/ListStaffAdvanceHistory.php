<?php

namespace App\Livewire\Staff;

use App\Models\StaffAdvanceTransaction;
use App\Models\User;
use App\Services\Staff\StaffAdvanceService;
use App\Services\Staff\StaffPermissions;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListStaffAdvanceHistory extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['transaction_date', 'user_number', 'name', 'type', 'amount', 'balance', 'notes'];

    public string $search = '';

    public ?string $userFilter = null;

    public ?string $typeFilter = null;

    #[On('staff-advances-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('staff-advance-history-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('staff-advance-history-filter')]
    public function applyFilter(string $filter): void
    {
        if ($filter === 'all' || $filter === '') {
            $this->userFilter = null;
            $this->typeFilter = null;
        } elseif (str_starts_with($filter, 'user:')) {
            $this->userFilter = substr($filter, strlen('user:'));
        } elseif (str_starts_with($filter, 'type:')) {
            $this->typeFilter = substr($filter, strlen('type:'));
        }

        $this->resetPage();
    }

    #[On('staff-advance-history-clear-filters')]
    public function clearFilters(): void
    {
        $this->userFilter = null;
        $this->typeFilter = null;
        $this->resetPage();
    }

    #[On('staff-advance-history-column-toggle')]
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
            $this->visibleColumns = ['transaction_date', 'name', 'amount'];
        }

        $this->dispatch('staff-advance-history-columns-sync', columns: $this->visibleColumns);
    }

    public function mount(): void
    {
        $this->dispatch('staff-advance-history-columns-sync', columns: $this->visibleColumns);
    }

    public function render(): View
    {
        $service = app(StaffAdvanceService::class);
        $restrictUserId = $this->restrictedUserId();

        $historyQuery = $service->historyQuery($restrictUserId)
            ->when(filled($this->userFilter), fn ($q) => $q->where('user_id', (int) $this->userFilter))
            ->when(filled($this->typeFilter), fn ($q) => $q->where('type', $this->typeFilter))
            ->when(filled($this->search) && $restrictUserId === null, function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->whereHas('user', function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle);
                });
            });

        $transactions = $historyQuery->paginate(20);

        $balanceAfter = [];
        foreach ($transactions as $transaction) {
            $balanceAfter[$transaction->id] = $service->balanceForUser($transaction->user);
        }

        return view('livewire.staff.advances.history', [
            'transactions' => $transactions,
            'balanceAfter' => $balanceAfter,
            'visibleColumns' => $this->visibleColumns,
        ]);
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
