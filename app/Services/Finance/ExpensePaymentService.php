<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpensePaymentService
{
    public function __construct(
        private readonly CashBookService $cashBook,
        private readonly BankAccountService $banks,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): ExpensePayment
    {
        $validated = $this->validate($data);
        $actor ??= auth()->user();

        $lockKey = 'finance-expense-payment:'
            .(($validated['source_wallet'] ?? 'cash') === ExpensePayment::WALLET_BANK
                ? 'bank:'.($validated['bank_account_id'] ?? 'none')
                : 'cash');

        return Cache::lock($lockKey, 15)->block(10, function () use ($validated, $actor): ExpensePayment {
            return DB::transaction(function () use ($validated, $actor): ExpensePayment {
                if (! filled($validated['payment_number'] ?? null)) {
                    $validated['payment_number'] = $this->nextPaymentNumber();
                }

                $validated['created_by'] = $actor?->id;
                $payment = ExpensePayment::query()->create($validated);
                $this->postLedger($payment, $actor);

                return $payment->fresh(['expense.category', 'bankAccount']);
            });
        });
    }

    /**
     * Master notes only — amount / wallet locked after create.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ExpensePayment $payment, array $data): ExpensePayment
    {
        $validated = validator($data, [
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $payment->update([
            'notes' => $validated['notes'] ?? null,
        ]);

        return $payment->fresh(['expense.category', 'bankAccount']);
    }

    public function delete(ExpensePayment $payment): void
    {
        throw ValidationException::withMessages([
            'payment' => [__('agricart.finance.expense_payments.messages.delete_locked')],
        ]);
    }

    public function nextPaymentNumber(): string
    {
        $next = (int) ExpensePayment::query()->max('id') + 1;

        do {
            $number = 'EXPAY-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (ExpensePayment::query()->where('payment_number', $number)->exists());

        return $number;
    }

    private function postLedger(ExpensePayment $payment, ?User $actor): void
    {
        $expense = Expense::query()->with('category')->findOrFail($payment->expense_id);
        $amount = (float) $payment->amount;
        $date = $payment->payment_date?->toDateString() ?? now()->toDateString();
        $description = __('agricart.finance.expense_payments.ledger.posted', [
            'name' => $expense->displayName('en'),
            'number' => $payment->payment_number,
            'head' => $expense->expense_number,
        ]);

        if ($payment->source_wallet === ExpensePayment::WALLET_CASH) {
            $this->cashBook->recordOut(
                amount: $amount,
                transactionDate: $date,
                description: $description,
                notes: $payment->notes,
                actor: $actor,
                expenseId: $expense->id,
                expensePaymentId: $payment->id,
            );

            return;
        }

        $account = BankAccount::query()->find($payment->bank_account_id);

        if ($account === null) {
            throw ValidationException::withMessages([
                'bank_account_id' => [__('agricart.finance.expense_payments.messages.bank_required')],
            ]);
        }

        $this->banks->recordExpensePayment(
            from: $account,
            amount: $amount,
            transactionDate: $date,
            description: $description,
            notes: $payment->notes,
            actor: $actor,
            expenseId: $expense->id,
            expensePaymentId: $payment->id,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $validated = validator($data, [
            'payment_number' => ['nullable', 'string', 'max:32', Rule::unique('expense_payments', 'payment_number')],
            'expense_id' => ['required', 'integer', 'exists:expenses,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_time' => ['nullable', 'date_format:H:i'],
            'source_wallet' => ['required', Rule::in(ExpensePayment::WALLETS)],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        if ($validated['source_wallet'] === ExpensePayment::WALLET_BANK && empty($validated['bank_account_id'])) {
            throw ValidationException::withMessages([
                'bank_account_id' => [__('agricart.finance.expense_payments.messages.bank_required')],
            ]);
        }

        if ($validated['source_wallet'] === ExpensePayment::WALLET_CASH) {
            $validated['bank_account_id'] = null;
        }

        $head = Expense::query()->find($validated['expense_id']);

        if ($head === null || ! $head->isActive()) {
            throw ValidationException::withMessages([
                'expense_id' => [__('agricart.finance.expense_payments.messages.head_required')],
            ]);
        }

        if ($validated['source_wallet'] === ExpensePayment::WALLET_BANK) {
            $bank = BankAccount::query()->find($validated['bank_account_id']);

            if ($bank === null || ! $bank->isActive()) {
                throw ValidationException::withMessages([
                    'bank_account_id' => [__('agricart.finance.expense_payments.messages.bank_required')],
                ]);
            }
        }

        return $validated;
    }
}
