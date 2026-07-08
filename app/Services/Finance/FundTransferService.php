<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\FundTransfer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

        $lockKey = 'finance-fund-transfer:' . ($actor?->id ?? 'guest') . ':' . hash('sha256', json_encode([
            $type,
            $amount,
            $date,
            $validated['source_wallet'] ?? null,
            $validated['source_bank_account_id'] ?? null,
            $validated['destination_bank_account_id'] ?? null,
            $validated['destination_supplier_id'] ?? null,
            $validated['destination_purchaser_id'] ?? null,
        ]));

        return Cache::lock($lockKey, 15)->block(10, function () use ($validated, $amount, $date, $notes, $type, $actor): FundTransfer {
            return DB::transaction(function () use ($validated, $amount, $date, $notes, $type, $actor): FundTransfer {
            $transfer = FundTransfer::query()->create([
                'transfer_number' => $this->nextTransferNumber(),
                'transaction_type' => $type,
                'source_wallet' => $validated['source_wallet'] ?? null,
                'source_bank_account_id' => $validated['source_bank_account_id'] ?? null,
                'destination_bank_account_id' => $validated['destination_bank_account_id'] ?? null,
                'destination_supplier_id' => $validated['destination_supplier_id'] ?? null,
                'destination_purchaser_id' => $validated['destination_purchaser_id'] ?? null,
                'amount' => $amount,
                'transaction_date' => $date,
                'transaction_time' => $validated['transaction_time'],
                'notes' => $notes,
                'created_by' => $actor?->id,
            ]);

            match ($type) {
                FundTransfer::TYPE_DEPOSIT => $this->postDeposit($transfer, $actor),
                FundTransfer::TYPE_TRANSFER => $this->postBankTransfer($transfer, $actor),
                FundTransfer::TYPE_VENDOR_PAYMENT => $this->postVendorPayment($transfer, $actor),
                FundTransfer::TYPE_EXPENSE_PAYMENT => $this->postExpensePayment($transfer, $actor),
                default => throw ValidationException::withMessages([
                    'transaction_type' => [__('agricart.finance.fund_transfers.messages.invalid_type')],
                ]),
            };

            return $transfer->fresh();
            });
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

    private function postExpensePayment(FundTransfer $transfer, ?User $actor): void
    {
        $purchaser = User::query()->findOrFail($transfer->destination_purchaser_id);
        $description = __('agricart.finance.fund_transfers.ledger.expense_payment', [
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
            'transaction_type' => ['required', 'in:deposit,transfer,vendor_payment,expense_payment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        $type = (string) ($data['transaction_type'] ?? '');

        if ($type === FundTransfer::TYPE_DEPOSIT) {
            $data['source_wallet'] = FundTransfer::WALLET_CASH;
            $rules['destination_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id'];
        }

        if ($type === FundTransfer::TYPE_TRANSFER) {
            $data['source_wallet'] = FundTransfer::WALLET_BANK;
            $rules['source_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id'];
            $rules['destination_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id', 'different:source_bank_account_id'];
        }

        if (in_array($type, [FundTransfer::TYPE_VENDOR_PAYMENT, FundTransfer::TYPE_EXPENSE_PAYMENT], true)) {
            $rules['source_wallet'] = ['required', 'in:cash,bank'];
            if (($data['source_wallet'] ?? '') === FundTransfer::WALLET_BANK) {
                $rules['source_bank_account_id'] = ['required', 'integer', 'exists:bank_accounts,id'];
            }
        }

        if ($type === FundTransfer::TYPE_VENDOR_PAYMENT) {
            $rules['destination_supplier_id'] = ['required', 'integer', 'exists:suppliers,id'];
        }

        if ($type === FundTransfer::TYPE_EXPENSE_PAYMENT) {
            $rules['destination_purchaser_id'] = ['required', 'integer', 'exists:users,id'];
        }

        return validator($data, $rules)->validate();
    }

    private function nextTransferNumber(): string
    {
        $next = (int) FundTransfer::query()->max('id') + 1;

        do {
            $number = 'FT-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (FundTransfer::query()->where('transfer_number', $number)->exists());

        return $number;
    }
}
