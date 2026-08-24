<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Services\Finance\BankAccountService;
use App\Support\PakistaniBanks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class BankAccountForm extends Component
{
    use AuthorizesSensitiveActions;

    public string $mode = 'create';

    public ?int $bankAccountId = null;

    public string $bank_key = '';

    public string $bankSearch = '';

    public string $account_title = '';

    public string $account_title_ur = '';

    public string $account_identifier_type = BankAccount::IDENTIFIER_ACCOUNT_NUMBER;

    public string $account_identifier = '';

    public string $status = BankAccount::STATUS_ACTIVE;

    public string $historyFrom = '';

    public string $historyTo = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('finance-bank-form-open')]
    public function openForm(?int $bankAccountId = null, ?string $mode = null): void
    {
        $resolvedMode = $mode ?? ($bankAccountId ? 'edit' : 'create');
        $this->authorizeAny(
            in_array($resolvedMode, ['view', 'history'], true)
                ? ['finance.bank-accounts.view', 'finance.bank-accounts.edit']
                : ($bankAccountId ? 'finance.bank-accounts.edit' : 'finance.bank-accounts.create')
        );

        $this->resetValidation();
        $this->statusMessage = null;

        if (in_array($resolvedMode, ['manage', 'view'], true)) {
            $resolvedMode = 'edit';
        }

        $this->mode = $resolvedMode;
        $this->bankAccountId = $bankAccountId;
        $this->historyFrom = now()->subDays(30)->format('Y-m-d');
        $this->historyTo = now()->format('Y-m-d');

        if ($this->bankAccountId !== null) {
            $this->loadAccount();
        } else {
            $this->resetAccountFields();
            $this->status = BankAccount::STATUS_ACTIVE;
        }
    }

    #[On('finance-bank-form-action')]
    public function handlePanelAction(string $action): void
    {
        $this->authorizeAny($this->requiredActionPermission());

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'print' => $this->printHistory(),
            'print_account_details' => $this->printAccountDetails(),
            'print_bank_details' => $this->printBankSummary(),
            default => null,
        };
    }

    public function saveAndClose(): void
    {
        $this->authorizeAny($this->bankAccountId ? 'finance.bank-accounts.edit' : 'finance.bank-accounts.create');

        if ($this->saving || ! $this->canEditFields()) {
            return;
        }

        $this->saving = true;
        $this->dispatch('finance-bank-form-busy', saving: true);

        try {
            $service = app(BankAccountService::class);
            $payload = $this->accountPayload();

            if ($this->mode === 'create') {
                $account = $service->create($payload);
                $this->mode = 'edit';
                $this->bankAccountId = $account->id;
            } elseif ($this->bankAccountId !== null) {
                $account = BankAccount::query()->findOrFail($this->bankAccountId);
                $service->update($account, $payload);
            }

            $this->dispatch('finance-banks-saved');
            $this->dispatch('close-form-panel');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
        } finally {
            $this->saving = false;
            $this->dispatch('finance-bank-form-busy', saving: false);
        }
    }

    public function selectBank(string $bankKey): void
    {
        if (! $this->canEditFields()) {
            return;
        }

        $this->bank_key = $bankKey;
        $this->bankSearch = '';
    }

    public function clearBank(): void
    {
        if (! $this->canEditFields()) {
            return;
        }

        $this->bank_key = '';
        $this->bankSearch = '';
    }

    public function printAccountDetails(): void
    {
        if ($this->bankAccountId === null) {
            return;
        }

        $this->dispatch('finance-banks-open-print', url: route('admin.bank-accounts.print', [
            'account' => $this->bankAccountId,
            'autoprint' => 1,
        ]));
    }

    public function printBankSummary(): void
    {
        if ($this->bankAccountId === null) {
            return;
        }

        $this->dispatch('finance-banks-open-print', url: route('admin.bank-accounts.print-summary', [
            'account' => $this->bankAccountId,
            'autoprint' => 1,
        ]));
    }

    public function printHistory(): void
    {
        if ($this->bankAccountId === null) {
            return;
        }

        $this->dispatch('finance-banks-open-print', url: route('admin.bank-accounts.history-print', [
            'account' => $this->bankAccountId,
            'from' => $this->historyFrom,
            'to' => $this->historyTo,
            'autoprint' => 1,
        ]));
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'history' || ! $this->canEditFields();
    }

    public function render(): View
    {
        $service = app(BankAccountService::class);
        $account = $this->bankAccountId !== null
            ? BankAccount::query()->find($this->bankAccountId)
            : null;

        $balance = $account instanceof BankAccount ? $service->balance($account) : 0.0;

        /** @var Collection<int, BankAccountTransaction> $historyTransactions */
        $historyTransactions = collect();

        if ($this->mode === 'history' && $account instanceof BankAccount) {
            $historyTransactions = BankAccountTransaction::query()
                ->where('bank_account_id', $account->id)
                ->with(['counterpartyBankAccount', 'supplier', 'sale'])
                ->when(filled($this->historyFrom), fn ($q) => $q->whereDate('transaction_date', '>=', $this->historyFrom))
                ->when(filled($this->historyTo), fn ($q) => $q->whereDate('transaction_date', '<=', $this->historyTo))
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->limit(500)
                ->get();
        }

        $showAccountFields = $this->mode !== 'history';
        $showExistingMeta = $account !== null && in_array($this->mode, ['edit', 'history'], true);

        return view('livewire.finance.bank-accounts.form', [
            'account' => $account,
            'balance' => $balance,
            'money' => fn (float $amount): string => $service->formatMoney($amount),
            'filteredBanks' => PakistaniBanks::search($this->bankSearch),
            'selectedBankNameEn' => PakistaniBanks::english($this->bank_key ?: null),
            'selectedBankNameUr' => PakistaniBanks::urdu($this->bank_key ?: null),
            'canEdit' => $this->canEditFields(),
            'readonly' => $this->isReadonly(),
            'showAccountFields' => $showAccountFields,
            'showExistingMeta' => $showExistingMeta,
            'isHistory' => $this->mode === 'history',
            'historyTransactions' => $historyTransactions,
            'identifierTypeOptions' => collect(BankAccount::IDENTIFIER_TYPES)
                ->mapWithKeys(fn (string $type): array => [$type => __('agricart.finance.banks.identifier_types.' . $type)])
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function accountPayload(): array
    {
        $titleEn = trim($this->account_title);

        return [
            'name_en' => $titleEn,
            'name_ur' => trim($this->account_title_ur) ?: null,
            'bank_key' => $this->bank_key ?: null,
            'account_title' => $titleEn,
            'account_identifier_type' => $this->account_identifier_type,
            'account_identifier' => trim($this->account_identifier),
            'opening_balance' => 0,
            'status' => $this->status,
            'notes' => null,
        ];
    }

    private function loadAccount(): void
    {
        $this->authorizeAny(
            $this->mode === 'history'
                ? ['finance.bank-accounts.view', 'finance.bank-accounts.edit']
                : 'finance.bank-accounts.edit'
        );
        $account = BankAccount::query()->find($this->bankAccountId);

        if ($account === null) {
            $this->resetAccountFields();

            return;
        }

        $this->bank_key = (string) ($account->bank_key ?? '');
        $this->bankSearch = '';
        $this->account_title = (string) ($account->account_title ?: $account->name_en);
        $this->account_title_ur = (string) ($account->name_ur ?? '');
        $this->account_identifier_type = in_array($account->account_identifier_type, BankAccount::IDENTIFIER_TYPES, true)
            ? (string) $account->account_identifier_type
            : (filled($account->iban) ? BankAccount::IDENTIFIER_IBAN : BankAccount::IDENTIFIER_ACCOUNT_NUMBER);
        $this->account_identifier = filled($account->iban)
            ? (string) $account->iban
            : (string) ($account->account_number ?? '');
        $this->status = (string) $account->status;
    }

    private function resetAccountFields(): void
    {
        $this->bank_key = '';
        $this->bankSearch = '';
        $this->account_title = '';
        $this->account_title_ur = '';
        $this->account_identifier_type = BankAccount::IDENTIFIER_ACCOUNT_NUMBER;
        $this->account_identifier = '';
    }

    private function canEditFields(): bool
    {
        if ($this->mode === 'history') {
            return false;
        }

        return $this->canAccessAny(
            $this->bankAccountId ? 'finance.bank-accounts.edit' : 'finance.bank-accounts.create'
        );
    }

    private function requiredActionPermission(): array|string
    {
        return $this->mode === 'history' || ! $this->canEditFields()
            ? ['finance.bank-accounts.view', 'finance.bank-accounts.edit']
            : ($this->bankAccountId ? 'finance.bank-accounts.edit' : 'finance.bank-accounts.create');
    }
}
