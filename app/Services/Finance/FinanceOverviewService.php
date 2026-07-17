<?php

namespace App\Services\Finance;

use App\Filament\Clusters\Finance\BankAccountsPage;
use App\Filament\Clusters\Finance\CashBankTransfersPage;
use App\Filament\Clusters\Finance\CashBookPage;
use App\Filament\Clusters\Finance\ExpensePaymentsPage;
use App\Filament\Clusters\Finance\ExpensesPage;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\FundTransfer;

class FinanceOverviewService
{
    /**
     * @return list<array{key: string, label: string, count: int|string, active: int|null, url: string, meta: string|null}>
     */
    public function statCards(): array
    {
        $cashBalance = app(CashBookService::class)->balance();

        $cards = [
            $this->card(
                'banks',
                BankAccount::query()->count(),
                BankAccount::query()->where('status', 'active')->count(),
                BankAccountsPage::class,
                __('agricart.finance.overview.meta.banks', [
                    'count' => number_format(BankAccount::query()->where('status', 'active')->count()),
                ]),
            ),
            $this->card(
                'transfers',
                FundTransfer::query()->count(),
                null,
                CashBankTransfersPage::class,
                null,
            ),
            $this->card(
                'expenses',
                Expense::query()->count(),
                Expense::query()->where('status', 'active')->count(),
                ExpensesPage::class,
                __('agricart.finance.overview.meta.expenses', [
                    'count' => number_format(Expense::query()->where('status', 'active')->count()),
                ]),
            ),
            $this->card(
                'payments',
                ExpensePayment::query()->count(),
                null,
                ExpensePaymentsPage::class,
                null,
            ),
            $this->card(
                'cash',
                number_format($cashBalance, 2).' PKR',
                null,
                CashBookPage::class,
                __('agricart.finance.overview.meta.cash'),
            ),
        ];

        $visible = array_filter($cards, function (array $card): bool {
            $pageClass = $card['page'];

            return $pageClass::canAccess();
        });

        return array_values(array_map(function (array $card): array {
            unset($card['page']);

            return $card;
        }, $visible));
    }

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, count: int|string, active: int|null, url: string, meta: string|null, page: class-string}
     */
    private function card(string $key, int|string $count, ?int $active, string $pageClass, ?string $meta): array
    {
        return [
            'key' => $key,
            'label' => (string) __('agricart.finance.overview.cards.'.$key),
            'count' => $count,
            'active' => $active,
            'url' => $pageClass::getUrl(),
            'meta' => $meta,
            'page' => $pageClass,
        ];
    }
}
