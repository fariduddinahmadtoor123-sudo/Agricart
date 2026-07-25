<?php

namespace App\Livewire\Finance;

use App\Models\BankAccount;
use App\Models\User;
use App\Services\Finance\BankAccountService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListBankAccounts extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['account_code', 'name', 'bank', 'account_number', 'balance', 'status'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('finance-banks-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('finance-banks-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('finance-banks-filter')]
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

    #[On('finance-banks-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('finance-banks-column-toggle')]
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
            $this->visibleColumns = ['name', 'balance'];
        }

        $this->dispatch('finance-banks-columns-sync', columns: $this->visibleColumns);
    }

    public function mount(): void
    {
        $this->dispatch('finance-banks-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.banks.actions.add'),
            resource: __('agricart.nav.finance.bank-accounts'),
            formKey: 'finance-bank',
            mode: 'create',
        );
    }

    public function openEdit(int $bankAccountId): void
    {
        $account = BankAccount::query()->find($bankAccountId);

        if ($account === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.banks.actions.edit', ['name' => $account->displayName()]),
            resource: __('agricart.nav.finance.bank-accounts'),
            formKey: 'finance-bank',
            bankAccountId: $bankAccountId,
            mode: 'edit',
        );
    }

    public function openHistory(int $bankAccountId): void
    {
        $account = BankAccount::query()->find($bankAccountId);

        if ($account === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.banks.actions.history', ['name' => $account->displayName()]),
            resource: __('agricart.nav.finance.bank-accounts'),
            formKey: 'finance-bank',
            bankAccountId: $bankAccountId,
            mode: 'history',
        );
    }

    public function render(): View
    {
        $service = app(BankAccountService::class);

        $accounts = BankAccount::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('account_code', 'like', $needle)
                        ->orWhere('account_number', 'like', $needle)
                        ->orWhere('bank_name_en', 'like', $needle)
                        ->orWhere('bank_name_ur', 'like', $needle)
                        ->orWhere('account_title', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('name_en')
            ->paginate(15);

        $balances = $service->balancesForIds(
            $accounts->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        );

        return view('livewire.finance.bank-accounts.list', [
            'accounts' => $accounts,
            'balances' => $balances,
            'money' => fn (float $amount): string => $service->formatMoney($amount),
            'canCreate' => $this->userCan('create'),
            'canEdit' => $this->userCan('edit'),
            'visibleColumns' => $this->visibleColumns,
        ]);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('finance.' . $action);
    }
}
