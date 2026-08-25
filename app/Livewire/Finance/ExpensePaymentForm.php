<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Services\Finance\ExpensePaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ExpensePaymentForm extends Component
{
    use AuthorizesSensitiveActions;
    use WithFileUploads;

    public string $mode = 'create';

    public ?int $paymentId = null;

    public string $payment_number = '';

    public ?int $expense_id = null;

    public string $amount = '';

    public string $payment_date = '';

    public string $payment_time = '';

    public string $source_wallet = ExpensePayment::WALLET_CASH;

    public ?int $bank_account_id = null;

    public string $notes = '';

    public ?TemporaryUploadedFile $bankReceiptUpload = null;

    public ?string $storedBankReceiptPath = null;

    public ?string $bankReceiptPreviewUrl = null;

    public bool $remove_bank_receipt = false;

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('finance-expense-payment-form-open')]
    public function openForm(?int $paymentId = null, ?string $mode = null): void
    {
        $resolvedMode = $mode ?? ($paymentId ? 'edit' : 'create');
        $this->authorizeAny(
            $resolvedMode === 'view'
                ? ['finance.expense-payments.view', 'finance.expense-payments.edit']
                : ($paymentId ? 'finance.expense-payments.edit' : 'finance.expense-payments.create')
        );

        $this->resetValidation();
        $this->statusMessage = null;
        $this->mode = $resolvedMode;
        $this->paymentId = $paymentId;

        if ($this->paymentId !== null) {
            $this->loadPayment();
        } else {
            $this->resetFields();
            $this->payment_number = app(ExpensePaymentService::class)->nextPaymentNumber();
            $this->payment_date = now()->format('Y-m-d');
            $this->payment_time = now()->format('H:i');
        }
    }

    #[On('finance-expense-payment-form-action')]
    public function handlePanelAction(string $action): void
    {
        $this->authorizeAny($this->requiredActionPermission());

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            'print' => $this->print(),
            default => null,
        };
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
        $this->authorizeAny($this->paymentId ? 'finance.expense-payments.edit' : 'finance.expense-payments.create');

        if ($this->persist()) {
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        $this->authorizeAny($this->paymentId ? 'finance.expense-payments.edit' : 'finance.expense-payments.create');

        if ($this->persist()) {
            $this->mode = 'create';
            $this->paymentId = null;
            $this->resetFields();
            $this->payment_number = app(ExpensePaymentService::class)->nextPaymentNumber();
            $this->payment_date = now()->format('Y-m-d');
            $this->payment_time = now()->format('H:i');
            $this->setStatus(__('agricart.finance.expense_payments.messages.saved_next'), 'success');
        }
    }

    public function print(): void
    {
        $this->authorizeAny(['finance.expense-payments.view', 'finance.expense-payments.edit']);

        if ($this->paymentId === null) {
            return;
        }

        $this->dispatch('finance-expense-payments-open-print', url: route('admin.expense-payments.print', [
            'payment' => $this->paymentId,
            'paper' => 'a4',
            'locale' => app()->getLocale(),
            'autoprint' => 1,
        ]));
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view' || ! $this->canEditFields();
    }

    public function paymentLocked(): bool
    {
        return $this->mode !== 'create';
    }

    public function selectedExpense(): ?Expense
    {
        if ($this->expense_id === null) {
            return null;
        }

        return Expense::query()->with('category')->find($this->expense_id);
    }

    public function render(): View
    {
        /** @var Collection<int, Expense> $expenses */
        $expenses = Expense::query()
            ->with('category')
            ->where('status', Expense::STATUS_ACTIVE)
            ->orderBy('name_en')
            ->get();

        /** @var Collection<int, BankAccount> $banks */
        $banks = BankAccount::query()
            ->where('status', BankAccount::STATUS_ACTIVE)
            ->orderBy('name_en')
            ->get();

        return view('livewire.finance.expense-payments.form', [
            'expenses' => $expenses,
            'banks' => $banks,
            'selectedExpense' => $this->selectedExpense(),
        ]);
    }

    private function persist(): bool
    {
        $this->authorizeAny($this->paymentId ? 'finance.expense-payments.edit' : 'finance.expense-payments.create');

        if ($this->saving || $this->mode === 'view' || ! $this->canEditFields()) {
            return false;
        }

        $this->saving = true;
        $this->dispatch('finance-expense-payment-form-busy', saving: true);

        try {
            if ($this->bankReceiptUpload !== null) {
                $this->validate([
                    'bankReceiptUpload' => ['nullable', 'image', 'max:12288'],
                ]);
            }

            $service = app(ExpensePaymentService::class);

            if ($this->mode === 'create') {
                $payment = $service->create($this->payload(), null, $this->bankReceiptUpload);
                $this->mode = 'edit';
                $this->paymentId = $payment->id;
                $this->payment_number = $payment->payment_number;
                $this->storedBankReceiptPath = $payment->bank_receipt_path;
                $this->bankReceiptPreviewUrl = $payment->bankReceiptUrl();
            } elseif ($this->paymentId !== null) {
                $payment = ExpensePayment::query()->findOrFail($this->paymentId);
                $payment = $service->update(
                    $payment,
                    ['notes' => $this->notes !== '' ? $this->notes : null],
                    $this->bankReceiptUpload,
                    $this->remove_bank_receipt,
                );
                $this->storedBankReceiptPath = $payment->bank_receipt_path;
                $this->bankReceiptPreviewUrl = $payment->bankReceiptUrl();
            }

            $this->bankReceiptUpload = null;
            $this->remove_bank_receipt = false;
            $this->dispatch('finance-expense-payments-saved');
            $this->setStatus(__('agricart.finance.expense_payments.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            $this->setStatus(collect($exception->errors())->flatten()->first() ?: __('agricart.finance.expense_payments.messages.save_failed'), 'error');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatch('finance-expense-payment-form-busy', saving: false);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'payment_number' => filled($this->payment_number) ? $this->payment_number : null,
            'expense_id' => $this->expense_id,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date,
            'payment_time' => $this->payment_time !== '' ? $this->payment_time : null,
            'source_wallet' => $this->source_wallet,
            'bank_account_id' => $this->source_wallet === ExpensePayment::WALLET_BANK ? $this->bank_account_id : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];
    }

    private function loadPayment(): void
    {
        $this->authorizeAny($this->mode === 'view' ? ['finance.expense-payments.view', 'finance.expense-payments.edit'] : 'finance.expense-payments.edit');
        $payment = ExpensePayment::query()->findOrFail($this->paymentId);
        $this->payment_number = (string) $payment->payment_number;
        $this->expense_id = $payment->expense_id;
        $this->amount = (string) $payment->amount;
        $this->payment_date = $payment->payment_date?->format('Y-m-d') ?? '';
        $this->payment_time = $payment->payment_time ? substr((string) $payment->payment_time, 0, 5) : '';
        $this->source_wallet = (string) $payment->source_wallet;
        $this->bank_account_id = $payment->bank_account_id;
        $this->notes = (string) ($payment->notes ?? '');
        $this->bankReceiptUpload = null;
        $this->remove_bank_receipt = false;
        $this->storedBankReceiptPath = $payment->bank_receipt_path;
        $this->bankReceiptPreviewUrl = $payment->bankReceiptUrl();
    }

    private function resetFields(): void
    {
        $this->payment_number = '';
        $this->expense_id = null;
        $this->amount = '';
        $this->payment_date = '';
        $this->payment_time = '';
        $this->source_wallet = ExpensePayment::WALLET_CASH;
        $this->bank_account_id = null;
        $this->notes = '';
        $this->bankReceiptUpload = null;
        $this->storedBankReceiptPath = null;
        $this->bankReceiptPreviewUrl = null;
        $this->remove_bank_receipt = false;
    }

    private function canEditFields(): bool
    {
        return $this->canAccessAny(
            $this->paymentId ? 'finance.expense-payments.edit' : 'finance.expense-payments.create'
        );
    }

    private function requiredActionPermission(): array|string
    {
        return $this->isReadonly()
            ? ['finance.expense-payments.view', 'finance.expense-payments.edit']
            : ($this->paymentId ? 'finance.expense-payments.edit' : 'finance.expense-payments.create');
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
