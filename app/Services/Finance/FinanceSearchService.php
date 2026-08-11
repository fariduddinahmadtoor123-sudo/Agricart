<?php

namespace App\Services\Finance;

use App\Filament\Clusters\Finance\BankAccountsPage;
use App\Filament\Clusters\Finance\ExpenseCategoriesPage;
use App\Filament\Clusters\Finance\ExpensePaymentsPage;
use App\Filament\Clusters\Finance\ExpensesPage;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayment;
use App\Support\Search\SearchHit;
use Illuminate\Database\Eloquent\Builder;

class FinanceSearchService
{
    /**
     * @return array<string, list<array{label: string, meta: string|null, url: string}>>
     */
    public function search(string $term, int $limitPerGroup = 6): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%'.$term.'%';

        return array_filter([
            'expense_categories' => ExpenseCategoriesPage::canAccess()
                ? $this->mapExpenseCategories($needle, $limitPerGroup)
                : [],
            'expense_heads' => ExpensesPage::canAccess()
                ? $this->mapExpenseHeads($needle, $limitPerGroup)
                : [],
            'expense_payments' => ExpensePaymentsPage::canAccess()
                ? $this->mapExpensePayments($needle, $limitPerGroup)
                : [],
            'bank_accounts' => BankAccountsPage::canAccess()
                ? $this->mapBankAccounts($needle, $limitPerGroup)
                : [],
        ], fn (array $items): bool => $items !== []);
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapExpenseCategories(string $needle, int $limit): array
    {
        $url = ExpenseCategoriesPage::getUrl();

        return ExpenseCategory::query()
            ->where(function (Builder $query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('code', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit($limit)
            ->get()
            ->map(function (ExpenseCategory $category) use ($url): array {
                $label = $category->displayName();

                return SearchHit::make(
                    $label,
                    filled($category->code) ? (string) $category->code : null,
                    $url,
                    null,
                    [
                        'formKey' => 'finance-expense-category',
                        'categoryId' => $category->id,
                        'title' => $label,
                        'resource' => __('agricart.nav.finance.expense-categories'),
                    ],
                );
            })
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapExpenseHeads(string $needle, int $limit): array
    {
        $url = ExpensesPage::getUrl();

        return Expense::query()
            ->where(function (Builder $query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('expense_number', 'like', $needle)
                    ->orWhere('information', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit($limit)
            ->get()
            ->map(function (Expense $expense) use ($url): array {
                $label = $expense->displayName();

                return SearchHit::make(
                    $label,
                    filled($expense->expense_number) ? (string) $expense->expense_number : null,
                    $url,
                    null,
                    [
                        'formKey' => 'finance-expense',
                        'expenseId' => $expense->id,
                        'title' => $label,
                        'resource' => __('agricart.nav.finance.expenses'),
                    ],
                );
            })
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapExpensePayments(string $needle, int $limit): array
    {
        $url = ExpensePaymentsPage::getUrl();

        return ExpensePayment::query()
            ->with('expense')
            ->where(function (Builder $query) use ($needle): void {
                $query->where('payment_number', 'like', $needle)
                    ->orWhere('notes', 'like', $needle)
                    ->orWhereHas('expense', function (Builder $expense) use ($needle): void {
                        $expense->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('expense_number', 'like', $needle);
                    });
            })
            ->orderByDesc('payment_date')
            ->limit($limit)
            ->get()
            ->map(function (ExpensePayment $payment) use ($url): array {
                $label = (string) $payment->payment_number;

                return SearchHit::make(
                    $label,
                    $payment->expense?->displayName(),
                    $url,
                    null,
                    [
                        'formKey' => 'finance-expense-payment',
                        'paymentId' => $payment->id,
                        'title' => $label,
                        'resource' => __('agricart.nav.finance.expense-payments'),
                    ],
                );
            })
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapBankAccounts(string $needle, int $limit): array
    {
        $url = BankAccountsPage::getUrl();

        return BankAccount::query()
            ->where(function (Builder $query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('account_code', 'like', $needle)
                    ->orWhere('account_title', 'like', $needle)
                    ->orWhere('account_number', 'like', $needle)
                    ->orWhere('iban', 'like', $needle)
                    ->orWhere('bank_name_en', 'like', $needle)
                    ->orWhere('bank_name_ur', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit($limit)
            ->get()
            ->map(function (BankAccount $account) use ($url): array {
                $label = (string) ($account->name_en ?: $account->name_ur ?: $account->account_code);

                return SearchHit::make(
                    $label,
                    filled($account->account_code) ? (string) $account->account_code : null,
                    $url,
                    null,
                    [
                        'formKey' => 'finance-bank',
                        'bankAccountId' => $account->id,
                        'title' => $label,
                        'resource' => __('agricart.nav.finance.bank-accounts'),
                    ],
                );
            })
            ->all();
    }
}
