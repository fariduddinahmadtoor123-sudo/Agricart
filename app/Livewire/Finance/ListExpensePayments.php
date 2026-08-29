<?php

namespace App\Livewire\Finance;

use App\Models\ExpensePayment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListExpensePayments extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['payment_number', 'date', 'expense', 'category', 'amount', 'wallet', 'receipt'];

    public string $search = '';

    public string $walletFilter = '';

    #[On('finance-expense-payments-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('finance-expense-payments-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('finance-expense-payments-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'wallet') {
            $this->walletFilter = $value;
            $this->resetPage();
        }
    }

    #[On('finance-expense-payments-clear-filters')]
    public function clearFilters(): void
    {
        $this->walletFilter = '';
        $this->resetPage();
    }

    #[On('finance-expense-payments-column-toggle')]
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
            $this->visibleColumns = ['payment_number', 'expense', 'amount'];
        }

        $this->dispatch('finance-expense-payments-columns-sync', columns: $this->visibleColumns);
    }

    public function mount(): void
    {
        $this->dispatch('finance-expense-payments-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.expense_payments.actions.add'),
            resource: __('agricart.nav.finance.expense-payments'),
            formKey: 'finance-expense-payment',
            mode: 'create',
        );
    }

    public function openView(int $paymentId): void
    {
        if (! $this->userCan('view')) {
            return;
        }

        $payment = ExpensePayment::query()->with('expense')->find($paymentId);

        if ($payment === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.expense_payments.actions.view', [
                'name' => $payment->expense?->displayName() ?? $payment->payment_number,
            ]),
            resource: __('agricart.nav.finance.expense-payments'),
            formKey: 'finance-expense-payment',
            paymentId: $paymentId,
            mode: 'view',
        );
    }

    public function openEdit(int $paymentId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $payment = ExpensePayment::query()->with('expense')->find($paymentId);

        if ($payment === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.expense_payments.actions.edit', [
                'name' => $payment->expense?->displayName() ?? $payment->payment_number,
            ]),
            resource: __('agricart.nav.finance.expense-payments'),
            formKey: 'finance-expense-payment',
            paymentId: $paymentId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $payments = ExpensePayment::query()
            ->with(['expense.category', 'bankAccount', 'fundTransfer'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('payment_number', 'like', $needle)
                        ->orWhere('notes', 'like', $needle)
                        ->orWhereHas('expense', function ($expense) use ($needle): void {
                            $expense->where('name_en', 'like', $needle)
                                ->orWhere('name_ur', 'like', $needle)
                                ->orWhere('expense_number', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->walletFilter), fn ($q) => $q->where('source_wallet', $this->walletFilter))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.finance.expense-payments.list', [
            'payments' => $payments,
            'money' => fn (float $value): string => number_format($value, 2, '.', ''),
            'canView' => $this->userCan('view'),
            'canEdit' => $this->userCan('edit'),
        ]);
    }

    private function userCan(string $ability): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'finance', 'expense-payments', $ability);
    }
}
