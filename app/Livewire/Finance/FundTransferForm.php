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

class FundTransferForm extends Component
{
    use AuthorizesSensitiveActions;

    public const TYPE_DEPOSIT = FundTransfer::TYPE_DEPOSIT;

    public const TYPE_WITHDRAWAL = FundTransfer::TYPE_WITHDRAWAL;

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

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('finance-fund-transfer-form-open')]
    public function openForm(?int $transferId = null, ?string $mode = null): void
    {
        $this->authorizeAny($transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);
        $this->resetValidation();
        $this->statusMessage = null;
        $this->mode = $mode ?? ($transferId ? 'edit' : 'create');
        $this->transferId = $transferId;
        $this->resetFormFields();
    }

    #[On('finance-fund-transfer-form-action')]
    public function handlePanelAction(string $action): void
    {
        $this->authorizeAny($this->transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);

        if ($action === 'save_close') {
            if ($this->saving) {
                return;
            }

            $this->saveAndClose();
        }
    }

    public function updatedTransactionType(): void
    {
        $this->authorizeAny($this->transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);
        $this->applyTypeDefaults();
    }

    public function updatedSourceWallet(): void
    {
        $this->authorizeAny($this->transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);

        if ($this->source_wallet === self::SOURCE_CASH) {
            $this->source_bank_account_id = null;
        }
    }

    public function saveAndClose(): void
    {
        $this->authorizeAny($this->transferId ? 'finance.cash-bank-transfers.edit' : ['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit']);

        if ($this->saving) {
            return;
        }

        $this->validate($this->rules());

        $this->saving = true;
        $this->dispatch('finance-fund-transfer-form-busy', saving: true);

        try {
            $transfer = app(FundTransferService::class)->record($this->payload(), auth()->user());
            $this->transaction_number = $transfer->transfer_number;
            $this->dispatch('finance-fund-transfers-saved');
            $this->dispatch('close-form-panel');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
        } finally {
            $this->saving = false;
            $this->dispatch('finance-fund-transfer-form-busy', saving: false);
        }
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
            'notes' => $this->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'transaction_type' => ['required', 'in:deposit,withdrawal,transfer,vendor_payment,customer_receipt,expense_payment,purchaser_payment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i'],
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
            self::TYPE_TRANSFER, self::TYPE_WITHDRAWAL => true,
            self::TYPE_VENDOR_PAYMENT, self::TYPE_CUSTOMER_RECEIPT, self::TYPE_EXPENSE_PAYMENT, self::TYPE_PURCHASER_PAYMENT => $this->source_wallet === self::SOURCE_BANK,
            default => false,
        };
    }

    public function requiresDestinationBank(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_DEPOSIT, self::TYPE_TRANSFER], true);
    }

    public function sourceIsLocked(): bool
    {
        return $this->transaction_type === self::TYPE_DEPOSIT;
    }

    public function sourceIsBankLocked(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_WITHDRAWAL, self::TYPE_TRANSFER], true);
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

    public function sourceWalletLabel(): string
    {
        return $this->destinationIsCustomer()
            ? __('agricart.finance.fund_transfers.fields.deposit_to')
            : __('agricart.finance.fund_transfers.sections.source');
    }

    public function render(): View
    {
        if (! $this->canAccessAny(['finance.cash-bank-transfers.create', 'finance.cash-bank-transfers.edit'])) {
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
        $this->transaction_number = '';
        $this->transaction_date = now()->format('Y-m-d');
        $this->transaction_time = now()->format('H:i');
        $this->applyTypeDefaults();
    }

    private function applyTypeDefaults(): void
    {
        $this->destination_bank_account_id = in_array($this->transaction_type, [self::TYPE_DEPOSIT, self::TYPE_TRANSFER], true)
            ? $this->destination_bank_account_id
            : null;
        $this->destination_supplier_id = $this->transaction_type === self::TYPE_VENDOR_PAYMENT ? $this->destination_supplier_id : null;
        $this->destination_customer_id = $this->transaction_type === self::TYPE_CUSTOMER_RECEIPT ? $this->destination_customer_id : null;
        $this->destination_purchaser_id = $this->transaction_type === self::TYPE_PURCHASER_PAYMENT ? $this->destination_purchaser_id : null;
        $this->destination_expense_id = $this->transaction_type === self::TYPE_EXPENSE_PAYMENT ? $this->destination_expense_id : null;

        match ($this->transaction_type) {
            self::TYPE_DEPOSIT => (function (): void {
                $this->source_wallet = self::SOURCE_CASH;
                $this->source_bank_account_id = null;
            })(),
            self::TYPE_WITHDRAWAL, self::TYPE_TRANSFER => (function (): void {
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
            self::TYPE_WITHDRAWAL => __('agricart.finance.fund_transfers.types.withdrawal'),
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

    private function formattedDateTime(): string
    {
        try {
            return \Carbon\Carbon::parse($this->transaction_date.' '.$this->transaction_time)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return '—';
        }
    }
}
