<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FundTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FundTransferService
{
    public function __construct(
        private BankAccountService $bankAccountService,
        private CashBookService $cashBookService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?User $actor = null): FundTransfer
    {
        $actor ??= auth()->user();
        $validated = $this->validate($data);
        $amount = round((float) $validated['amount'], 2);
        $date = (string) $validated['transaction_date'];
        $notes = filled($validated['notes'] ?? null) ? (string) $validated['notes'] : null;
        $type = (string) $validated['transaction_type'];

        return DB::transaction(function () use ($validated, $amount, $date, $notes, $type, $actor): FundTransfer {
            $transfer = FundTransfer::query()->create([
                'transfer_number' => $this->nextTransferNumber(),
                'transaction_type' => $type,
                'source_wallet' => $validated['source_wallet'] ?? null,
                'source_bank_account_id' => $validated['source_bank_account_id'] ?? null,
                'destination_bank_account_id' => $validated['destination_bank_account_id'] ?? null,
                'destination_supplier_id' => $validated['destination_supplier_id'] ?? null,
                'destination_customer_id' => $validated['destination_customer_id'] ?? null,
                'destination_purchaser_id' => $validated['destination_purchaser_id'] ?? null,
                'destination_expense_id' => $validated['destination_expense_id'] ?? null,
                'amount' => $amount,
                'transaction_date' => $date,
                'transaction_time' => $validated['transaction_time'],
                'notes' => $notes,
                'created_by' => $actor?->id,
            ]);

            match ($type) {
                FundTransfer::TYPE_DEPOSIT => $this->postDeposit($transfer, $actor),
                FundTransfer::TYPE_WITHDRAWAL => $this->postWithdrawal($transfer, $actor),
                FundTransfer::TYPE_TRANSFER => $this->postBankTransfer($transfer, $actor),
                FundTransfer::TYPE_VENDOR_PAYMENT => $this->postVendorPayment($transfer, $actor),
                FundTransfer::TYPE_CUSTOMER_RECEIPT => $this->postCustomerReceipt($transfer, $actor),
                FundTransfer::TYPE_EXPENSE_PAYMENT => $this->postExpenseHeadPayment($transfer, $actor),
                FundTransfer::TYPE_PURCHASER_PAYMENT => $this->postPurchaserPayment($transfer, $actor),
                default => throw ValidationException::withMessages([
                    'transaction_type' => [__('agricart.finance.fund_transfers.messages.invalid_type')],
                ]),
            };

            return $transfer->fresh();
        });
    }

    public function formatMoney(float $amount): string
    {
        return $this->bankAccountService->formatMoney($amount);
    }

    private function postDeposit(FundTransfer $transfer, ?User $actor): void
    {
        $bank = BankAccount::query()->findOrFail($transfer->destination_bank_account_id);
        $description = __('agricart.finance.fund_transfers.ledger.cash_deposit', [
            'number' => $transfer->transfer_number,
        ]);

        $this->cashBookService->recordOut(
            (float) $transfer->amount,
            $transfer->transaction_date->toDateString(),
            $description,
            $transfer->id,
            $transfer->notes,
            $actor,
        );

        $this->bankAccountService->recordCashDeposit(
            $bank,
            (float) $transfer->amount,
            $transfer->transaction_date->toDateString(),
            $description,
            $transfer->notes,
            $transfer->id,
            $actor,
        );
    }

    private function postWithdrawal(FundTransfer $transfer, ?User $actor): void
    {
        $bank = BankAccount::query()->findOrFail($transfer->source_bank_account_id);
        $description = __('agricart.finance.fund_transfers.ledger.cash_withdrawal', [
            'number' => $transfer->transfer_number,
        ]);

        $this->bankAccountService->recordCashWithdrawal(
            $bank,
            (float) $transfer->amount,
            $transfer->transaction_date->toDateString(),
            $description,
            $transfer->notes,
            $transfer->id,
            $actor,
        );

        $this->cashBookService->recordIn(
            (float) $transfer->amount,
            $transfer->transaction_date->toDateString(),
            $description,
            $transfer->id,
            $transfer->notes,
            $actor,
        );
    }

    private function postBankTransfer(FundTransfer $transfer, ?User $actor): void
    {
        $from = BankAccount::query()->findOrFail($transfer->source_bank_account_id);
        $to = BankAccount::query()->findOrFail($transfer->destination_bank_account_id);

        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'destination_bank_account_id' => [__('agricart.finance.banks.messages.same_account')],
            ]);
        }

        $this->bankAccountService->transferToBank(
            $from,
            $to,
            (float) $transfer->amount,
            $transfer->transaction_date->toDateString(),
            $transfer->notes,
            $actor,
            $transfer->id,
        );
    }

    private function postVendorPayment(FundTransfer $transfer, ?User $actor): void
    {
        $supplier = Supplier::query()->findOrFail($transfer->destination_supplier_id);
        $description = __('agricart.finance.fund_transfers.ledger.vendor_payment', [
            'name' => $supplier->displayBusinessName(),
            'number' => $transfer->transfer_number,
        ]);

        if ($transfer->source_wallet === FundTransfer::WALLET_CASH) {
            $this->cashBookService->recordOut(
                (float) $transfer->amount,
                $transfer->transaction_date->toDateString(),
                $description,
                $transfer->id,
                $transfer->notes,
                $actor,
            );
        } else {
            $from = BankAccount::query()->findOrFail($transfer->source_bank_account_id);
            $this->bankAccountService->paySupplier(
                $from,
                $supplier,
                (float) $transfer->amount,
                $transfer->transaction_date->toDateString(),
                $transfer->notes,
                $actor,
                $transfer->id,
            );
        }

        $this->applySupplierPayment($supplier, (float) $transfer->amount);
    }

    private function postCustomerReceipt(FundTransfer $transfer, ?User $actor): void
    {
        $customer = Customer::query()->findOrFail($transfer->destination_customer_id);
        $description = __('agricart.finance.fund_transfers.ledger.customer_receipt', [
            'name' => $customer->displayName(),
            'number' => $transfer->transfer_number,
        ]);

        if ($transfer->source_wallet === FundTransfer::WALLET_CASH) {
            $this->cashBookService->recordIn(
                (float) $transfer->amount,
                $transfer->transaction_date->toDateString(),
                $description,
                $transfer->id,
                $transfer->notes,
                $actor,
            );
        } else {
            $into = BankAccount::query()->findOrFail($transfer->source_bank_account_id);
            $this->bankAccountService->receiveCustomerPayment(
                $into,
                $customer,
                (float) $transfer->amount,
                $transfer->transaction_date->toDateString(),
                $transfer->notes,
                $actor,
                $transfer->id,
            );
        }
    }

    private function postExpenseHeadPayment(FundTransfer $transfer, ?User $actor): void
    {
        $expense = Expense::query()->findOrFail($transfer->destination_expense_id);

        $payment = app(ExpensePaymentService::class)->create([
            'expense_id' => $expense->id,
            'amount' => $transfer->amount,
            'payment_date' => $transfer->transaction_date->toDateString(),
            'payment_time' => substr((string) $transfer->transaction_time, 0, 5),
            'source_wallet' => $transfer->source_wallet,
            'bank_account_id' => $transfer->source_bank_account_id,
            'notes' => $transfer->notes,
            'fund_transfer_id' => $transfer->id,
        ], $actor);

        $transfer->update([
            'expense_payment_id' => $payment->id,
        ]);
    }

    private function postPurchaserPayment(FundTransfer $transfer, ?User $actor): void
    {
        $purchaser = User::query()->findOrFail($transfer->destination_purchaser_id);
        $description = __('agricart.finance.fund_transfers.ledger.purchaser_payment', [
            'name' => $purchaser->name,
            'number' => $transfer->transfer_number,
        ]);

        if ($transfer->source_wallet === FundTransfer::WALLET_CASH) {
            $this->cashBookService->recordOut(
                (float) $transfer->amount,
                $transfer->transaction_date->toDateString(),
                $description,
                $transfer->id,
                $transfer->notes,
                $actor,
            );
        } else {
            $from = BankAccount::query()->findOrFail($transfer->source_bank_account_id);
            $this->bankAccountService->recordExpensePayment(
                $from,
                (float) $transfer->amount,
                $transfer->transaction_date->toDateString(),
                $description,
                $transfer->notes,
                $transfer->id,
                $actor,
            );
        }
    }

    private function applySupplierPayment(Supplier $supplier, float $amount): void
    {
        $supplier = Supplier::query()->lockForUpdate()->find($supplier->id);

        if ($supplier === null) {
            return;
        }

        $supplier->update([
            'opening_balance' => round((float) $supplier->opening_balance - $amount, 2),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $rules = [
            'transaction_type' => ['required', 'in:deposit,withdrawal,transfer,vendor_payment,customer_receipt,expense_payment,purchaser_payment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        $type = (string) ($data['transaction_type'] ?? '');

        if ($type === FundTransfer::TYPE_DEPOSIT) {
            $data['source_wallet'] = FundTransfer::WALLET_CASH;
            $rules['source_wallet'] = ['required', 'in:cash,bank'];
            $rules['destination_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id'];
        }

        if ($type === FundTransfer::TYPE_TRANSFER) {
            $data['source_wallet'] = FundTransfer::WALLET_BANK;
            $rules['source_wallet'] = ['required', 'in:cash,bank'];
            $rules['source_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id'];
            $rules['destination_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id', 'different:source_bank_account_id'];
        }

        if ($type === FundTransfer::TYPE_WITHDRAWAL) {
            $data['source_wallet'] = FundTransfer::WALLET_BANK;
            $rules['source_wallet'] = ['required', 'in:cash,bank'];
            $rules['source_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id'];
        }

        if (in_array($type, [FundTransfer::TYPE_VENDOR_PAYMENT, FundTransfer::TYPE_CUSTOMER_RECEIPT, FundTransfer::TYPE_EXPENSE_PAYMENT, FundTransfer::TYPE_PURCHASER_PAYMENT], true)) {
            $rules['source_wallet'] = ['required', 'in:cash,bank'];
            if (($data['source_wallet'] ?? '') === FundTransfer::WALLET_BANK) {
                $rules['source_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id'];
            }
        }

        if ($type === FundTransfer::TYPE_VENDOR_PAYMENT) {
            $rules['destination_supplier_id'] = ['required', 'integer', 'exists:suppliers,id'];
        }

        if ($type === FundTransfer::TYPE_CUSTOMER_RECEIPT) {
            $rules['destination_customer_id'] = ['required', 'integer', 'exists:customers,id'];
        }

        if ($type === FundTransfer::TYPE_EXPENSE_PAYMENT) {
            $rules['destination_expense_id'] = ['required', 'integer', 'exists:expenses,id'];
        }

        if ($type === FundTransfer::TYPE_PURCHASER_PAYMENT) {
            $rules['destination_purchaser_id'] = ['required', 'integer', 'exists:users,id'];
        }

        return validator($data, $rules)->validate();
    }

    private function nextTransferNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'fund_transfers.transfer_number',
            'FT-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('fund_transfers', 'transfer_number', 'FT-'),
        );
    }
}
