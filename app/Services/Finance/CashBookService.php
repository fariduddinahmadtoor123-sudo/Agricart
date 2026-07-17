<?php

namespace App\Services\Finance;

use App\Models\CashBookTransaction;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CashBookService
{
    public function balance(): float
    {
        $in = (float) CashBookTransaction::query()
            ->where('direction', CashBookTransaction::DIRECTION_IN)
            ->sum('amount');
        $out = (float) CashBookTransaction::query()
            ->where('direction', CashBookTransaction::DIRECTION_OUT)
            ->sum('amount');

        return round($in - $out, 2);
    }

    public function recordOut(
        float $amount,
        string $transactionDate,
        ?string $description,
        ?int $fundTransferId = null,
        ?string $notes = null,
        ?User $actor = null,
        ?int $expenseId = null,
        ?int $expensePaymentId = null,
        ?int $saleId = null,
        ?int $saleReturnId = null,
    ): CashBookTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.finance.banks.messages.amount_positive')],
            ]);
        }

        $this->assertSufficientBalance($amount);

        return CashBookTransaction::query()->create([
            'fund_transfer_id' => $fundTransferId,
            'expense_id' => $expenseId,
            'expense_payment_id' => $expensePaymentId,
            'sale_id' => $saleId,
            'sale_return_id' => $saleReturnId,
            'direction' => CashBookTransaction::DIRECTION_OUT,
            'amount' => $amount,
            'transaction_date' => $transactionDate,
            'description' => $description,
            'notes' => $notes,
            'created_by' => $actor?->id,
        ]);
    }

    public function recordIn(
        float $amount,
        string $transactionDate,
        ?string $description,
        ?int $fundTransferId = null,
        ?string $notes = null,
        ?User $actor = null,
        ?int $expenseId = null,
        ?int $expensePaymentId = null,
        ?int $saleId = null,
        ?int $saleReturnId = null,
    ): CashBookTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.finance.banks.messages.amount_positive')],
            ]);
        }

        return CashBookTransaction::query()->create([
            'fund_transfer_id' => $fundTransferId,
            'expense_id' => $expenseId,
            'expense_payment_id' => $expensePaymentId,
            'sale_id' => $saleId,
            'sale_return_id' => $saleReturnId,
            'direction' => CashBookTransaction::DIRECTION_IN,
            'amount' => $amount,
            'transaction_date' => $transactionDate,
            'description' => $description,
            'notes' => $notes,
            'created_by' => $actor?->id,
        ]);
    }

    public function recordSaleReceiptIfApplicable(Sale $sale): void
    {
        if ($sale->payment_method !== 'cash') {
            return;
        }

        $paid = (float) $sale->amount_paid;

        if ($paid <= 0) {
            return;
        }

        if (CashBookTransaction::query()->where('sale_id', $sale->id)->exists()) {
            return;
        }

        $this->recordIn(
            $paid,
            $sale->sale_date?->toDateString() ?? now()->toDateString(),
            __('agricart.finance.cash_book.ledger.sale_receipt', ['number' => $sale->sale_number]),
            saleId: $sale->id,
            actor: auth()->user() instanceof User ? auth()->user() : null,
        );
    }

    public function recordSaleReturnRefundIfApplicable(SaleReturn $return): void
    {
        if ($return->payment_method !== 'cash') {
            return;
        }

        $paid = (float) $return->amount_paid;

        if ($paid <= 0) {
            return;
        }

        if (CashBookTransaction::query()->where('sale_return_id', $return->id)->exists()) {
            return;
        }

        $this->recordOut(
            $paid,
            $return->return_date?->toDateString() ?? now()->toDateString(),
            __('agricart.finance.cash_book.ledger.sale_return_refund', ['number' => $return->return_number]),
            saleReturnId: $return->id,
            actor: auth()->user() instanceof User ? auth()->user() : null,
        );
    }

    public function assertSufficientBalance(float $amount): void
    {
        if ($this->balance() + 0.001 < $amount) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.finance.fund_transfers.messages.insufficient_cash')],
            ]);
        }
    }
}
