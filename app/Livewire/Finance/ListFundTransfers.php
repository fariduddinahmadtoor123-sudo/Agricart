<?php

namespace App\Livewire\Finance;

use App\Models\FundTransfer;
use App\Models\User;
use App\Services\Finance\FundTransferService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListFundTransfers extends Component
{
    use WithPagination;

    public string $search = '';

    #[On('finance-fund-transfers-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('finance-fund-transfers-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    public function openCreate(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.fund_transfers.actions.add'),
            resource: __('agricart.nav.finance.cash-bank-transfers'),
            formKey: 'finance-fund-transfer',
            mode: 'create',
        );
    }

    public function openView(int $transferId): void
    {
        if (! $this->userCan('view')) {
            return;
        }

        $transfer = FundTransfer::query()->find($transferId);

        if ($transfer === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.fund_transfers.actions.view', [
                'number' => $transfer->transfer_number,
            ]),
            resource: __('agricart.nav.finance.cash-bank-transfers'),
            formKey: 'finance-fund-transfer',
            transferId: $transferId,
            mode: 'view',
        );
    }

    public function openEdit(int $transferId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $transfer = FundTransfer::query()->find($transferId);

        if ($transfer === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.finance.fund_transfers.actions.edit', [
                'number' => $transfer->transfer_number,
            ]),
            resource: __('agricart.nav.finance.cash-bank-transfers'),
            formKey: 'finance-fund-transfer',
            transferId: $transferId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $service = app(FundTransferService::class);

        $transfers = FundTransfer::query()
            ->with(['sourceBankAccount', 'destinationBankAccount', 'destinationSupplier', 'destinationCustomer', 'destinationPurchaser', 'destinationExpense'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('transfer_number', 'like', $needle)
                        ->orWhere('notes', 'like', $needle)
                        ->orWhere('handled_by_name', 'like', $needle)
                        ->orWhereHas('destinationExpense', function ($expense) use ($needle): void {
                            $expense->where('expense_number', 'like', $needle)
                                ->orWhere('name_en', 'like', $needle)
                                ->orWhere('name_ur', 'like', $needle);
                        })
                        ->orWhereHas('destinationPurchaser', function ($user) use ($needle): void {
                            $user->where('name', 'like', $needle);
                        });
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.finance.fund-transfers.list', [
            'transfers' => $transfers,
            'money' => fn (float $amount): string => $service->formatMoney($amount),
            'typeLabel' => fn (FundTransfer $transfer): string => (string) (__('agricart.finance.fund_transfers.types.' . $transfer->transaction_type) ?: $transfer->transaction_type),
            'sourceLabel' => fn (FundTransfer $transfer): string => $this->sourceLabel($transfer),
            'destinationLabel' => fn (FundTransfer $transfer): string => $this->destinationLabel($transfer),
            'canView' => $this->userCan('view'),
            'canEdit' => $this->userCan('edit'),
        ]);
    }

    private function sourceLabel(FundTransfer $transfer): string
    {
        if ($transfer->transaction_type === FundTransfer::TYPE_EXTERNAL_DEPOSIT) {
            return __('agricart.finance.fund_transfers.source_wallets.external');
        }

        if ($transfer->transaction_type === FundTransfer::TYPE_CUSTOMER_RECEIPT) {
            return $transfer->source_wallet === FundTransfer::WALLET_CASH
                ? __('agricart.finance.fund_transfers.source_wallets.cash')
                : ($transfer->sourceBankAccount?->displayName() ?? '—');
        }

        if ($transfer->transaction_type === FundTransfer::TYPE_DEPOSIT || $transfer->source_wallet === FundTransfer::WALLET_CASH) {
            return __('agricart.finance.fund_transfers.source_wallets.cash');
        }

        return $transfer->sourceBankAccount?->displayName() ?? '—';
    }

    private function destinationLabel(FundTransfer $transfer): string
    {
        return match ($transfer->transaction_type) {
            FundTransfer::TYPE_DEPOSIT, FundTransfer::TYPE_EXTERNAL_DEPOSIT, FundTransfer::TYPE_TRANSFER => $transfer->destinationBankAccount?->displayName() ?? '—',
            FundTransfer::TYPE_WITHDRAWAL => __('agricart.finance.fund_transfers.source_wallets.cash'),
            FundTransfer::TYPE_EXTERNAL_WITHDRAWAL => __('agricart.finance.fund_transfers.source_wallets.external'),
            FundTransfer::TYPE_VENDOR_PAYMENT => $transfer->destinationSupplier?->displayBusinessName() ?? '—',
            FundTransfer::TYPE_CUSTOMER_RECEIPT => $transfer->destinationCustomer?->displayName() ?? '—',
            FundTransfer::TYPE_EXPENSE_PAYMENT => $transfer->destinationExpense?->displayName()
                ?? (string) ($transfer->destinationPurchaser?->name ?? '—'),
            FundTransfer::TYPE_PURCHASER_PAYMENT => (string) ($transfer->destinationPurchaser?->name ?? '—'),
            default => '—',
        };
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

        return \App\Services\Access\ModulePageAccess::can($user, 'finance', 'cash-bank-transfers', $action);
    }
}
