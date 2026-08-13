<?php

namespace App\Livewire\Finance;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListExpenseCategories extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['code', 'name', 'kind', 'status'];

    public string $search = '';

    public string $statusFilter = '';

    public string $kindFilter = '';

    #[On('finance-expense-categories-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('finance-expense-categories-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('finance-expense-categories-filter')]
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

        if ($field === 'kind') {
            $this->kindFilter = $value;
            $this->resetPage();
        }
    }

    #[On('finance-expense-categories-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->kindFilter = '';
        $this->resetPage();
    }

    #[On('finance-expense-categories-column-toggle')]
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
            $this->visibleColumns = ['name', 'status'];
        }

        $this->dispatch('finance-expense-categories-columns-sync', columns: $this->visibleColumns);
    }

    public function mount(): void
    {
        $this->dispatch('finance-expense-categories-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.expense_categories.actions.add'),
            resource: __('agricart.nav.finance.expense-categories'),
            formKey: 'finance-expense-category',
            mode: 'create',
        );
    }

    public function openEdit(int $categoryId): void
    {
        $category = ExpenseCategory::query()->find($categoryId);

        if ($category === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.expense_categories.actions.edit', ['name' => $category->displayName()]),
            resource: __('agricart.nav.finance.expense-categories'),
            formKey: 'finance-expense-category',
            categoryId: $categoryId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $categories = ExpenseCategory::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('code', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
            ->when(filled($this->kindFilter), fn ($q) => $q->where('kind', $this->kindFilter))
            ->orderBy('name_en')
            ->paginate(20);

        return view('livewire.finance.expense-categories.list', [
            'categories' => $categories,
        ]);
    }

    private function userCan(string $ability): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'finance', 'expense-categories', $ability);
    }
}
