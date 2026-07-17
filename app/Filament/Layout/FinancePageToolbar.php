<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Finance\OverviewPage;

class FinancePageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return FinanceSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Finance';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'finance';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        return match ($config['resourceSlug']) {
            'cash-bank-transfers' => [
                ...$config,
                'livewirePrefix' => 'finance-fund-transfers',
                'interactive' => true,
                'formKey' => 'finance-fund-transfer',
                'addLabel' => static::userCan('create') ? __('agricart.finance.fund_transfers.actions.add') : null,
            ],
            'bank-accounts' => [
                ...$config,
                'livewirePrefix' => 'finance-banks',
                'columnDataAttr' => 'data-finance-bank-column',
                'interactive' => true,
                'formKey' => 'finance-bank',
                'addLabel' => static::userCan('create') ? __('agricart.finance.banks.actions.add') : null,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.finance.banks.list.col_code'), 'value' => 'account_code', 'visible' => true],
                    ['label' => __('agricart.finance.banks.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.finance.banks.list.col_bank'), 'value' => 'bank', 'visible' => true],
                    ['label' => __('agricart.finance.banks.list.col_account_number'), 'value' => 'account_number', 'visible' => true],
                    ['label' => __('agricart.finance.banks.list.col_balance'), 'value' => 'balance', 'visible' => true],
                    ['label' => __('agricart.finance.banks.list.col_status'), 'value' => 'status', 'visible' => true],
                ],
            ],
            'expense-categories' => [
                ...$config,
                'livewirePrefix' => 'finance-expense-categories',
                'columnDataAttr' => 'data-finance-expense-category-column',
                'interactive' => true,
                'formKey' => 'finance-expense-category',
                'addLabel' => static::userCan('create') ? __('agricart.finance.expense_categories.actions.add') : null,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                    ['label' => __('agricart.finance.expense_categories.kinds.operating'), 'value' => 'kind:operating'],
                    ['label' => __('agricart.finance.expense_categories.kinds.other'), 'value' => 'kind:other'],
                ],
                'columns' => [
                    ['label' => __('agricart.finance.expense_categories.list.col_code'), 'value' => 'code', 'visible' => true],
                    ['label' => __('agricart.finance.expense_categories.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.finance.expense_categories.list.col_kind'), 'value' => 'kind', 'visible' => true],
                    ['label' => __('agricart.finance.expense_categories.list.col_status'), 'value' => 'status', 'visible' => true],
                ],
            ],
            'expenses' => [
                ...$config,
                'livewirePrefix' => 'finance-expenses',
                'columnDataAttr' => 'data-finance-expense-column',
                'interactive' => true,
                'formKey' => 'finance-expense',
                'addLabel' => static::userCan('create') ? __('agricart.finance.expenses.actions.add') : null,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.finance.expenses.list.col_number'), 'value' => 'expense_number', 'visible' => true],
                    ['label' => __('agricart.finance.expenses.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.finance.expenses.list.col_category'), 'value' => 'category', 'visible' => true],
                    ['label' => __('agricart.finance.expenses.list.col_status'), 'value' => 'status', 'visible' => true],
                ],
            ],
            'expense-payments' => [
                ...$config,
                'livewirePrefix' => 'finance-expense-payments',
                'columnDataAttr' => 'data-finance-expense-payment-column',
                'interactive' => true,
                'formKey' => 'finance-expense-payment',
                'addLabel' => static::userCan('create') ? __('agricart.finance.expense_payments.actions.add') : null,
                'filters' => [
                    ['label' => __('agricart.finance.expense_payments.wallets.cash'), 'value' => 'wallet:cash'],
                    ['label' => __('agricart.finance.expense_payments.wallets.bank'), 'value' => 'wallet:bank'],
                ],
                'columns' => [
                    ['label' => __('agricart.finance.expense_payments.list.col_number'), 'value' => 'payment_number', 'visible' => true],
                    ['label' => __('agricart.finance.expense_payments.list.col_date'), 'value' => 'date', 'visible' => true],
                    ['label' => __('agricart.finance.expense_payments.list.col_expense'), 'value' => 'expense', 'visible' => true],
                    ['label' => __('agricart.finance.expense_payments.list.col_category'), 'value' => 'category', 'visible' => true],
                    ['label' => __('agricart.finance.expense_payments.list.col_amount'), 'value' => 'amount', 'visible' => true],
                    ['label' => __('agricart.finance.expense_payments.list.col_wallet'), 'value' => 'wallet', 'visible' => true],
                ],
            ],
            default => $config,
        };
    }
}
