<?php

namespace App\Livewire\Finance;

use App\Models\Expense;
use App\Models\User;
use App\Services\Finance\ExpenseScheduleService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListExpenses extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['expense_number', 'name', 'category', 'schedule', 'estimated_cost', 'status'];

    public string $search = '';

    public string $statusFilter = '';

    public string $dueFilter = '';

    public string $estimateFrom = '';

    public string $estimateTo = '';

    private string $estimateToolbarTotal = '—';

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

    #[On('finance-expenses-set-estimate-range')]
    public function setEstimateRange(string $from = '', string $to = ''): void
    {
        if (filled($from)) {
            $this->estimateFrom = $from;
        }

        if (filled($to)) {
            $this->estimateTo = $to;
        }
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

        if ($field === 'due') {
            $this->dueFilter = $value;
            $this->resetPage();
        }
    }

    #[On('finance-expenses-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->dueFilter = '';
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
        $this->estimateFrom = now()->startOfMonth()->toDateString();
        $this->estimateTo = now()->endOfMonth()->toDateString();
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

    public function markCycleDone(int $expenseId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $expense = Expense::query()->with(['payments', 'billCycles'])->find($expenseId);

        if ($expense === null) {
            return;
        }

        try {
            app(ExpenseScheduleService::class)->markOldestOpenDone(
                $expense,
                auth()->user() instanceof User ? auth()->user() : null,
            );
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title((string) collect($exception->errors())->flatten()->first())
                ->send();

            return;
        }

        $this->resetPage();
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
        $schedule = app(ExpenseScheduleService::class);
        $from = $this->estimateStart();
        $to = $this->estimateEnd();
        $query = $this->expenseQuery($schedule);
        $this->estimateToolbarTotal = $this->formatMoney(
            $schedule->estimatedTotalForPeriod((clone $query)->get(), $from, $to),
        );

        return view('livewire.finance.expenses.list', [
            'expenses' => (clone $query)->paginate(20),
            'canEdit' => $this->userCan('edit'),
            'schedule' => $schedule,
            'money' => fn (float $value): string => $this->formatMoney($value),
        ]);
    }

    public function rendered(): void
    {
        $this->dispatch('finance-expenses-toolbar-sync',
            estimateFrom: $this->estimateStart()->toDateString(),
            estimateTo: $this->estimateEnd()->toDateString(),
            estimateTotal: $this->estimateToolbarTotal,
        );
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2).' PKR';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Expense>
     */
    private function expenseQuery(ExpenseScheduleService $schedule)
    {
        $relations = ['category', 'payments'];

        if (Schema::hasTable('expense_bill_cycles')) {
            $relations[] = 'billCycles';
        }

        return Expense::query()
            ->with($relations)
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('expense_number', 'like', $needle)
                        ->orWhere('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('information', 'like', $needle);

                    if (Schema::hasColumn('expenses', 'detail_1')) {
                        $inner->orWhere('detail_1', 'like', $needle)
                            ->orWhere('detail_2', 'like', $needle)
                            ->orWhere('detail_3', 'like', $needle)
                            ->orWhere('detail_4', 'like', $needle);
                    }
                });
            })
            ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dueFilter === 'issued', function ($query) use ($schedule): void {
                $ids = $schedule->expenseIdsWithDueStatus([Expense::DUE_ISSUED, Expense::DUE_SOON, Expense::DUE_OVERDUE]);
                $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
            })
            ->when($this->dueFilter === 'due_soon', function ($query) use ($schedule): void {
                $ids = $schedule->expenseIdsWithDueStatus([Expense::DUE_SOON]);
                $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
            })
            ->when($this->dueFilter === 'overdue', function ($query) use ($schedule): void {
                $ids = $schedule->expenseIdsWithDueStatus([Expense::DUE_OVERDUE]);
                $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
            })
            ->orderBy('name_en')
            ->orderByDesc('id');
    }

    private function estimateStart(): Carbon
    {
        $from = filled($this->estimateFrom) ? Carbon::parse($this->estimateFrom)->startOfDay() : now()->startOfMonth();
        $to = filled($this->estimateTo) ? Carbon::parse($this->estimateTo)->startOfDay() : now()->endOfMonth()->startOfDay();

        return $from->lte($to) ? $from : $to;
    }

    private function estimateEnd(): Carbon
    {
        $from = filled($this->estimateFrom) ? Carbon::parse($this->estimateFrom)->startOfDay() : now()->startOfMonth();
        $to = filled($this->estimateTo) ? Carbon::parse($this->estimateTo)->startOfDay() : now()->endOfMonth()->startOfDay();

        return $from->lte($to) ? $to : $from;
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
