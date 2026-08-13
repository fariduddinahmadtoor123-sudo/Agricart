<?php

namespace App\Livewire\Finance;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListExpenses extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['expense_number', 'name', 'category', 'status'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('finance-expenses-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('finance-expenses-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('finance-expenses-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status') {
            $this->statusFilter = $value;
            $this->resetPage();
        }
    }

    #[On('finance-expenses-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('finance-expenses-column-toggle')]
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
            $this->visibleColumns = ['expense_number', 'name', 'category'];
        }

        $this->dispatch('finance-expenses-columns-sync', columns: $this->visibleColumns);
    }

    public function mount(): void
    {
        $this->dispatch('finance-expenses-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.expenses.actions.add'),
            resource: __('agricart.nav.finance.expenses'),
            formKey: 'finance-expense',
            mode: 'create',
        );
    }

    public function openEdit(int $expenseId): void
    {
        $expense = Expense::query()->find($expenseId);

        if ($expense === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.expenses.actions.edit', ['name' => $expense->displayName()]),
            resource: __('agricart.nav.finance.expenses'),
            formKey: 'finance-expense',
            expenseId: $expenseId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $expenses = Expense::query()
            ->with('category')
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('expense_number', 'like', $needle)
                        ->orWhere('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('information', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('name_en')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.finance.expenses.list', [
            'expenses' => $expenses,
        ]);
    }

    private function userCan(string $ability): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'finance', 'expenses', $ability);
    }
}
