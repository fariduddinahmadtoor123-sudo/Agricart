<?php

namespace App\Services\Finance;

use App\Models\CashBookTransaction;
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
    ): CashBookTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.finance.banks.messages.amount_positive')],
            ]);
        }

        return CashBookTransaction::query()->create([
            'fund_transfer_id' => $fundTransferId,
            'direction' => CashBookTransaction::DIRECTION_IN,
            'amount' => $amount,
            'transaction_date' => $transactionDate,
            'description' => $description,
            'notes' => $notes,
            'created_by' => $actor?->id,
        ]);
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
