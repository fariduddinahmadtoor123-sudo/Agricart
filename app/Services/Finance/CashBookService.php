<?php

namespace App\Services\Finance;

use App\Models\CashBookTransaction;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        ?int $onlineSaleOrderId = null,
        ?string $sourceKey = null,
    ): CashBookTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.finance.banks.messages.amount_positive')],
            ]);
        }

        return DB::transaction(function () use ($amount, $transactionDate, $description, $fundTransferId, $notes, $actor, $expenseId, $expensePaymentId, $saleId, $saleReturnId, $onlineSaleOrderId, $sourceKey): CashBookTransaction {
            $this->lockWallet();
            $this->assertSufficientBalance($amount);

            $transaction = CashBookTransaction::query()->create([
                'fund_transfer_id' => $fundTransferId,
                'expense_id' => $expenseId,
                'expense_payment_id' => $expensePaymentId,
                'sale_id' => $saleId,
                'sale_return_id' => $saleReturnId,
                'online_sale_order_id' => $onlineSaleOrderId,
                'direction' => CashBookTransaction::DIRECTION_OUT,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'description' => $description,
                'notes' => $notes,
                'source_key' => $sourceKey,
                'created_by' => $actor?->id,
            ]);
            $this->syncLockedBalance();

            return $transaction;
        });
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
        ?int $onlineSaleOrderId = null,
        ?string $sourceKey = null,
    ): CashBookTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.finance.banks.messages.amount_positive')],
            ]);
        }

        return DB::transaction(function () use ($amount, $transactionDate, $description, $fundTransferId, $notes, $actor, $expenseId, $expensePaymentId, $saleId, $saleReturnId, $onlineSaleOrderId, $sourceKey): CashBookTransaction {
            $this->lockWallet();
            $transaction = CashBookTransaction::query()->create([
                'fund_transfer_id' => $fundTransferId,
                'expense_id' => $expenseId,
                'expense_payment_id' => $expensePaymentId,
                'sale_id' => $saleId,
                'sale_return_id' => $saleReturnId,
                'online_sale_order_id' => $onlineSaleOrderId,
                'direction' => CashBookTransaction::DIRECTION_IN,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'description' => $description,
                'notes' => $notes,
                'source_key' => $sourceKey,
                'created_by' => $actor?->id,
            ]);
            $this->syncLockedBalance();

            return $transaction;
        });
    }

    /**
     * POS sale cash book covers merchandise only. Post leftover paid shipping for cash online orders.
     */
    public function recordOnlineOrderShippingIfApplicable(OnlineSaleOrder $order, float $saleAmountPaid): void
    {
        $method = $order->payment_method ?: $order->customer_payment_method;

        if ($method !== 'cash') {
            return;
        }

        $shippingAmount = round((float) ($order->shipping_amount ?? 0), 2);
        $orderPaid = round((float) $order->amount_paid, 2);
        $shippingCash = round(min($shippingAmount, max(0, $orderPaid - round($saleAmountPaid, 2))), 2);

        if ($shippingCash <= 0) {
            return;
        }

        $sourceKey = 'online-sale-order-shipping:'.$order->id;

        if (CashBookTransaction::query()->where('source_key', $sourceKey)->exists()) {
            return;
        }

        $this->recordIn(
            $shippingCash,
            $order->order_date?->toDateString() ?? now()->toDateString(),
            __('agricart.finance.cash_book.ledger.online_order_shipping', ['number' => $order->order_number]),
            onlineSaleOrderId: $order->id,
            sourceKey: $sourceKey,
            actor: auth()->user() instanceof User ? auth()->user() : null,
        );
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

    private function lockWallet(): void
    {
        DB::table('cash_wallet_balances')->insertOrIgnore([
            'id' => 1,
            'balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cash_wallet_balances')->where('id', 1)->lockForUpdate()->first();
    }

    private function syncLockedBalance(): void
    {
        DB::table('cash_wallet_balances')->where('id', 1)->update([
            'balance' => $this->balance(),
            'updated_at' => now(),
        ]);
    }
}
