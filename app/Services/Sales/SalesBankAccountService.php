<?php

namespace App\Services\Sales;

use App\Models\BankAccount;
use App\Services\Finance\BankAccountService;

class SalesBankAccountService
{
    /**
     * @return list<array{id: int, label: string}>
     */
    public function options(): array
    {
        return BankAccount::query()
            ->where('status', BankAccount::STATUS_ACTIVE)
            ->orderBy('name_en')
            ->get()
            ->map(fn (BankAccount $account): array => [
                'id' => (int) $account->id,
                'label' => $this->labelFor($account),
            ])
            ->all();
    }

    public function labelFor(BankAccount $account): string
    {
        $parts = array_filter([
            $account->displayName(),
            $account->displayBankName(),
            filled($account->account_number) ? '••' . substr((string) $account->account_number, -4) : null,
        ]);

        return implode(' — ', $parts);
    }

    public function formatMoney(float $amount): string
    {
        return app(BankAccountService::class)->formatMoney($amount);
    }
}
