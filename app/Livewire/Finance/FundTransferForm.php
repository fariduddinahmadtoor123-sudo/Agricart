<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FundTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Finance\FundTransferService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class FundTransferForm extends Component
{
    use AuthorizesSensitiveActions;
    use WithFileUploads;

    public const TYPE_DEPOSIT = FundTransfer::TYPE_DEPOSIT;

    public const TYPE_WITHDRAWAL = FundTransfer::TYPE_WITHDRAWAL;

    public const TYPE_EXTERNAL_DEPOSIT = FundTransfer::TYPE_EXTERNAL_DEPOSIT;

    public const TYPE_EXTERNAL_WITHDRAWAL = FundTransfer::TYPE_EXTERNAL_WITHDRAWAL;

    public const TYPE_TRANSFER = FundTransfer::TYPE_TRANSFER;

    public const TYPE_VENDOR_PAYMENT = FundTransfer::TYPE_VENDOR_PAYMENT;

    public const TYPE_CUSTOMER_RECEIPT = FundTransfer::TYPE_CUSTOMER_RECEIPT;

    public const TYPE_EXPENSE_PAYMENT = FundTransfer::TYPE_EXPENSE_PAYMENT;

    public const TYPE_PURCHASER_PAYMENT = FundTransfer::TYPE_PURCHASER_PAYMENT;

    public const SOURCE_CASH = 'cash';

    public const SOURCE_BANK = 'bank';

    public string $mode = 'create';

    public ?int $transferId = null;

    public string $transaction_type = self::TYPE_DEPOSIT;

    /** @var self::SOURCE_CASH|self::SOURCE_BANK */
    public string $source_wallet = self::SOURCE_CASH;

    public ?int $source_bank_account_id = null;

    public ?int $destination_bank_account_id = null;

    public ?int $destination_supplier_id = null;

    public ?int $destination_customer_id = null;

    public ?int $destination_purchaser_id = null;

    public ?int $destination_expense_id = null;

    public string $amount = '';

    public string $transaction_date = '';

    public string $transaction_time = '';

    public string $transaction_number = '';

    public string $notes = '';

    public string $handled_by_name = '';

    public ?TemporaryUploadedFile $bankReceiptUpload = null;

    public ?string $storedBankReceiptPath = null;

    public ?string $bankReceiptPreviewUrl = null;

    public bool $remove_bank_receipt = false;

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('finance-fund-transfer-form-open')]
    public function openForm(?int $transferId = null, ?string $mode = null): void
    {
        $resolvedMode = $mode ?? ($transferId ? 'edit' : 'create');
        $this->authorizeAny(
            $resolvedMode === 'view'
                ? ['finance.cash-bank-transfers.view', 'finance.cash-bank-transfers.edit']
                : ($transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit'])
        );

        $this->resetValidation();
        $this->statusMessage = null;
        $this->mode = $resolvedMode;
        $this->transferId = $transferId;

        if ($this->transferId !== null) {
            $this->loadTransfer();
        } else {
            $this->resetFormFields();
        }
    }

    #[On('finance-fund-transfer-form-action')]
    public function handlePanelAction(string $action): void
    {
        $this->authorizeAny($this->requiredActionPermission());

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'print' => $this->print(),
            default => null,
        };
    }

    public function updatedTransactionType(): void
    {
        if ($this->transferLocked() || $this->isReadonly()) {
            return;
        }

        $this->authorizeAny(['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);
        $this->applyTypeDefaults();
    }

    public function updatedSourceWallet(): void
    {
        if ($this->transferLocked() || $this->isReadonly()) {
            return;
        }

        $this->authorizeAny(['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);

        if ($this->source_wallet === self::SOURCE_CASH) {
            $this->source_bank_account_id = null;
        }
    }

    public function updatedBankReceiptUpload(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->validateOnly('bankReceiptUpload', [
            'bankReceiptUpload' => ['nullable', 'image', 'max:12288'],
        ]);

        $this->bankReceiptPreviewUrl = $this->bankReceiptUpload?->temporaryUrl();
        $this->remove_bank_receipt = false;
    }

    public function removeBankReceipt(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->bankReceiptUpload = null;
        $this->bankReceiptPreviewUrl = null;
        $this->storedBankReceiptPath = null;
        $this->remove_bank_receipt = true;
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('close-form-panel');
        }
    }

    public function print(): void
    {
        $this->authorizeAny(['finance.cash-bank-transfers.view', 'finance.cash-bank-transfers.edit']);

        if ($this->transferId === null) {
            return;
        }

        $this->dispatch('finance-fund-transfers-open-print', url: route('admin.fund-transfers.print', [
            'transfer' => $this->transferId,
            'paper' => 'a4',
            'locale' => app()->getLocale(),
            'autoprint' => 1,
        ]));
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view' || ! $this->canEditFields();
    }

    public function transferLocked(): bool
    {
        return $this->mode !== 'create';
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'transaction_type' => $this->transaction_type,
            'source_wallet' => $this->source_wallet,
            'source_bank_account_id' => $this->source_bank_account_id,
            'destination_bank_account_id' => $this->destination_bank_account_id,
            'destination_supplier_id' => $this->destination_supplier_id,
            'destination_customer_id' => $this->destination_customer_id,
            'destination_purchaser_id' => $this->destination_purchaser_id,
            'destination_expense_id' => $this->destination_expense_id,
            'amount' => $this->amount,
            'transaction_date' => $this->transaction_date,
            'transaction_time' => $this->transaction_time,
            'notes' => $this->notes !== '' ? $this->notes : null,
            'handled_by_name' => $this->handled_by_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        if ($this->transferLocked()) {
            return [
                'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }

        $rules = [
            'transaction_type' => ['required', 'in:deposit,withdrawal,external_deposit,external_withdrawal,transfer,vendor_payment,customer_receipt,expense_payment,purchaser_payment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i'],
            'handled_by_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->requiresSourceBank()) {
            $rules['source_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id'];
        }

        if ($this->requiresDestinationBank()) {
            $destRules = ['required', 'integer', 'exists:bank_accounts,id'];
            if ($this->transaction_type === self::TYPE_TRANSFER) {
                $destRules[] = 'different:source_bank_account_id';
            }
            $rules['destination_bank_account_id'] = $destRules;
        }

        if ($this->requiresHandledByName()) {
            $rules['handled_by_name'] = ['required', 'string', 'max:120'];
        }

        if (in_array($this->transaction_type, [self::TYPE_VENDOR_PAYMENT, self::TYPE_CUSTOMER_RECEIPT, self::TYPE_EXPENSE_PAYMENT, self::TYPE_PURCHASER_PAYMENT], true)) {
            $rules['source_wallet'] = ['required', 'in:cash,bank'];
        }

        if ($this->transaction_type === self::TYPE_VENDOR_PAYMENT) {
            $rules['destination_supplier_id'] = ['required', 'integer', 'exists:suppliers,id'];
        }

        if ($this->transaction_type === self::TYPE_CUSTOMER_RECEIPT) {
            $rules['destination_customer_id'] = ['required', 'integer', 'exists:customers,id'];
        }

        if ($this->transaction_type === self::TYPE_EXPENSE_PAYMENT) {
            $rules['destination_expense_id'] = ['required', 'integer', 'exists:expenses,id'];
        }

        if ($this->transaction_type === self::TYPE_PURCHASER_PAYMENT) {
            $rules['destination_purchaser_id'] = ['required', 'integer', 'exists:users,id'];
        }

        return $rules;
    }

    public function requiresSourceBank(): bool
    {
        return match ($this->transaction_type) {
            self::TYPE_TRANSFER, self::TYPE_WITHDRAWAL, self::TYPE_EXTERNAL_WITHDRAWAL => true,
            self::TYPE_VENDOR_PAYMENT, self::TYPE_CUSTOMER_RECEIPT, self::TYPE_EXPENSE_PAYMENT, self::TYPE_PURCHASER_PAYMENT => $this->source_wallet === self::SOURCE_BANK,
            default => false,
        };
    }

    public function requiresDestinationBank(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_DEPOSIT, self::TYPE_EXTERNAL_DEPOSIT, self::TYPE_TRANSFER], true);
    }

    public function requiresHandledByName(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_EXTERNAL_DEPOSIT, self::TYPE_EXTERNAL_WITHDRAWAL], true);
    }

    public function sourceIsLocked(): bool
    {
        return $this->transaction_type === self::TYPE_DEPOSIT;
    }

    public function sourceIsExternal(): bool
    {
        return $this->transaction_type === self::TYPE_EXTERNAL_DEPOSIT;
    }

    public function sourceIsBankLocked(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_WITHDRAWAL, self::TYPE_EXTERNAL_WITHDRAWAL, self::TYPE_TRANSFER], true);
    }

    public function destinationIsVendor(): bool
    {
        return $this->transaction_type === self::TYPE_VENDOR_PAYMENT;
    }

    public function destinationIsCustomer(): bool
    {
        return $this->transaction_type === self::TYPE_CUSTOMER_RECEIPT;
    }

    public function destinationIsPurchaser(): bool
    {
        return $this->transaction_type === self::TYPE_PURCHASER_PAYMENT;
    }

    public function destinationIsExpense(): bool
    {
        return $this->transaction_type === self::TYPE_EXPENSE_PAYMENT;
    }

    public function destinationIsCash(): bool
    {
        return $this->transaction_type === self::TYPE_WITHDRAWAL;
    }

    public function destinationIsExternal(): bool
    {
        return $this->transaction_type === self::TYPE_EXTERNAL_WITHDRAWAL;
    }

    public function sourceWalletLabel(): string
    {
        return $this->destinationIsCustomer()
            ? __('agricart.finance.fund_transfers.fields.deposit_to')
            : __('agricart.finance.fund_transfers.sections.source');
    }

    public function render(): View
    {
        if (! $this->canAccessAny(['finance.cash-bank-transfers.view', 'finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit'])) {
            return view('livewire.finance.fund-transfers.form', [
                'transactionTypeOptions' => [],
                'sourceWalletOptions' => [],
                'bankAccountOptions' => [],
                'destinationBankOptions' => [],
                'supplierOptions' => [],
                'customerOptions' => [],
                'purchaserOptions' => [],
                'expenseOptions' => [],
                'formattedDateTime' => '—',
            ]);
        }

        $bankAccounts = BankAccount::query()
            ->where('status', BankAccount::STATUS_ACTIVE)
            ->orderBy('name_en')
            ->get();

        $destinationBanks = $bankAccounts;
        if ($this->transaction_type === self::TYPE_TRANSFER && $this->source_bank_account_id) {
            $destinationBanks = $bankAccounts->where('id', '!=', $this->source_bank_account_id);
        }

        return view('livewire.finance.fund-transfers.form', [
            'transactionTypeOptions' => $this->transactionTypeOptions(),
            'sourceWalletOptions' => $this->sourceWalletOptions(),
            'bankAccountOptions' => $this->bankAccountOptions($bankAccounts),
            'destinationBankOptions' => $this->bankAccountOptions($destinationBanks),
            'supplierOptions' => $this->supplierOptions(),
            'customerOptions' => $this->customerOptions(),
            'purchaserOptions' => $this->purchaserOptions(),
            'expenseOptions' => $this->expenseOptions(),
            'formattedDateTime' => $this->formattedDateTime(),
        ]);
    }

    private function persist(): bool
    {
        $this->authorizeAny($this->transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);

        if ($this->saving || $this->mode === 'view' || ! $this->canEditFields()) {
            return false;
        }

        $this->validate($this->rules());

        if ($this->bankReceiptUpload !== null) {
            $this->validate([
                'bankReceiptUpload' => ['nullable', 'image', 'max:12288'],
            ]);
        }

        $this->saving = true;
        $this->dispatch('finance-fund-transfer-form-busy', saving: true);

        try {
            $service = app(FundTransferService::class);

            if ($this->mode === 'create') {
                $transfer = $service->record($this->payload(), auth()->user(), $this->bankReceiptUpload);
                $this->mode = 'edit';
                $this->transferId = $transfer->id;
                $this->transaction_number = $transfer->transfer_number;
                $this->storedBankReceiptPath = $transfer->bank_receipt_path;
                $this->bankReceiptPreviewUrl = $transfer->bankReceiptUrl();
            } elseif ($this->transferId !== null) {
                $transfer = FundTransfer::query()->findOrFail($this->transferId);
                $transfer = $service->updateMeta(
                    $transfer,
                    $this->notes !== '' ? $this->notes : null,
                    $this->bankReceiptUpload,
                    $this->remove_bank_receipt,
                );
                $this->storedBankReceiptPath = $transfer->bank_receipt_path;
                $this->bankReceiptPreviewUrl = $transfer->bankReceiptUrl();
            } else {
                return false;
            }

            $this->bankReceiptUpload = null;
            $this->remove_bank_receipt = false;
            $this->dispatch('finance-fund-transfers-saved');
            $this->setStatus(__('agricart.finance.fund_transfers.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return false;
        } finally {
            $this->saving = false;
            $this->dispatch('finance-fund-transfer-form-busy', saving: false);
        }
    }

    private function loadTransfer(): void
    {
        $this->authorizeAny(
            $this->mode === 'view'
                ? ['finance.cash-bank-transfers.view', 'finance.cash-bank-transfers.edit']
                : 'finance.cash-bank-transfers.edit'
        );

        $transfer = FundTransfer::query()->findOrFail($this->transferId);
        $this->transaction_type = (string) $transfer->transaction_type;
        $this->source_wallet = (string) ($transfer->source_wallet ?: self::SOURCE_CASH);
        $this->source_bank_account_id = $transfer->source_bank_account_id;
        $this->destination_bank_account_id = $transfer->destination_bank_account_id;
        $this->destination_supplier_id = $transfer->destination_supplier_id;
        $this->destination_customer_id = $transfer->destination_customer_id;
        $this->destination_purchaser_id = $transfer->destination_purchaser_id;
        $this->destination_expense_id = $transfer->destination_expense_id;
        $this->amount = (string) $transfer->amount;
        $this->transaction_date = $transfer->transaction_date?->format('Y-m-d') ?? '';
        $this->transaction_time = $transfer->transaction_time ? substr((string) $transfer->transaction_time, 0, 5) : '';
        $this->transaction_number = (string) $transfer->transfer_number;
        $this->notes = (string) ($transfer->notes ?? '');
        $this->handled_by_name = (string) ($transfer->handled_by_name ?? '');
        $this->bankReceiptUpload = null;
        $this->remove_bank_receipt = false;
        $this->storedBankReceiptPath = $transfer->bank_receipt_path;
        $this->bankReceiptPreviewUrl = $transfer->bankReceiptUrl();
    }

    private function resetFormFields(): void
    {
        $this->transaction_type = self::TYPE_DEPOSIT;
        $this->source_wallet = self::SOURCE_CASH;
        $this->source_bank_account_id = null;
        $this->destination_bank_account_id = null;
        $this->destination_supplier_id = null;
        $this->destination_customer_id = null;
        $this->destination_purchaser_id = null;
        $this->destination_expense_id = null;
        $this->amount = '';
        $this->notes = '';
        $this->handled_by_name = '';
        $this->transaction_number = '';
        $this->transaction_date = now()->format('Y-m-d');
        $this->transaction_time = now()->format('H:i');
        $this->bankReceiptUpload = null;
        $this->storedBankReceiptPath = null;
        $this->bankReceiptPreviewUrl = null;
        $this->remove_bank_receipt = false;
        $this->applyTypeDefaults();
    }

    private function applyTypeDefaults(): void
    {
        $this->destination_bank_account_id = in_array($this->transaction_type, [self::TYPE_DEPOSIT, self::TYPE_EXTERNAL_DEPOSIT, self::TYPE_TRANSFER], true)
            ? $this->destination_bank_account_id
            : null;
        $this->destination_supplier_id = $this->transaction_type === self::TYPE_VENDOR_PAYMENT ? $this->destination_supplier_id : null;
        $this->destination_customer_id = $this->transaction_type === self::TYPE_CUSTOMER_RECEIPT ? $this->destination_customer_id : null;
        $this->destination_purchaser_id = $this->transaction_type === self::TYPE_PURCHASER_PAYMENT ? $this->destination_purchaser_id : null;
        $this->destination_expense_id = $this->transaction_type === self::TYPE_EXPENSE_PAYMENT ? $this->destination_expense_id : null;

        if (! $this->requiresHandledByName()) {
            $this->handled_by_name = '';
        }

        match ($this->transaction_type) {
            self::TYPE_DEPOSIT => (function (): void {
                $this->source_wallet = self::SOURCE_CASH;
                $this->source_bank_account_id = null;
            })(),
            self::TYPE_EXTERNAL_DEPOSIT => (function (): void {
                $this->source_wallet = self::SOURCE_CASH;
                $this->source_bank_account_id = null;
            })(),
            self::TYPE_WITHDRAWAL, self::TYPE_EXTERNAL_WITHDRAWAL, self::TYPE_TRANSFER => (function (): void {
                $this->source_wallet = self::SOURCE_BANK;
            })(),
            default => (function (): void {
                if ($this->source_wallet === self::SOURCE_CASH) {
                    $this->source_bank_account_id = null;
                }
            })(),
        };
    }

    /**
     * @return array<string, string>
     */
    private function transactionTypeOptions(): array
    {
        return [
            self::TYPE_DEPOSIT => __('agricart.finance.fund_transfers.types.deposit'),
            self::TYPE_EXTERNAL_DEPOSIT => __('agricart.finance.fund_transfers.types.external_deposit'),
            self::TYPE_WITHDRAWAL => __('agricart.finance.fund_transfers.types.withdrawal'),
            self::TYPE_EXTERNAL_WITHDRAWAL => __('agricart.finance.fund_transfers.types.external_withdrawal'),
            self::TYPE_TRANSFER => __('agricart.finance.fund_transfers.types.transfer'),
            self::TYPE_VENDOR_PAYMENT => __('agricart.finance.fund_transfers.types.vendor_payment'),
            self::TYPE_CUSTOMER_RECEIPT => __('agricart.finance.fund_transfers.types.customer_receipt'),
            self::TYPE_EXPENSE_PAYMENT => __('agricart.finance.fund_transfers.types.expense_payment'),
            self::TYPE_PURCHASER_PAYMENT => __('agricart.finance.fund_transfers.types.purchaser_payment'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sourceWalletOptions(): array
    {
        return [
            self::SOURCE_CASH => __('agricart.finance.fund_transfers.source_wallets.cash'),
            self::SOURCE_BANK => __('agricart.finance.fund_transfers.source_wallets.bank'),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BankAccount>|iterable<int, BankAccount>  $accounts
     * @return array<int, string>
     */
    private function bankAccountOptions(iterable $accounts): array
    {
        $options = [];

        foreach ($accounts as $account) {
            $options[$account->id] = $account->displayName().($account->displayBankName() ? ' — '.$account->displayBankName() : '');
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private function supplierOptions(): array
    {
        return Supplier::query()
            ->orderBy('business_name_en')
            ->get()
            ->mapWithKeys(fn (Supplier $supplier): array => [
                $supplier->id => $supplier->displayBusinessName(),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function customerOptions(): array
    {
        return Customer::query()
            ->orderBy('display_name')
            ->orderBy('name_en')
            ->get()
            ->mapWithKeys(fn (Customer $customer): array => [
                $customer->id => $customer->displayName(),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function purchaserOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function expenseOptions(): array
    {
        return Expense::query()
            ->where('status', Expense::STATUS_ACTIVE)
            ->orderBy('name_en')
            ->get()
            ->mapWithKeys(fn (Expense $expense): array => [
                $expense->id => $expense->expense_number.' — '.$expense->displayName(),
            ])
            ->all();
    }

    public function typeHint(): string
    {
        return __('agricart.finance.fund_transfers.hints.'.$this->transaction_type);
    }

    private function canEditFields(): bool
    {
        return $this->canAccessAny(
            $this->transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']
        );
    }

    private function requiredActionPermission(): array|string
    {
        return $this->isReadonly()
            ? ['finance.cash-bank-transfers.view', 'finance.cash-bank-transfers.edit']
            : ($this->transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function formattedDateTime(): string
    {
        try {
            return \Carbon\Carbon::parse($this->transaction_date.' '.$this->transaction_time)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return '—';
        }
    }
}
