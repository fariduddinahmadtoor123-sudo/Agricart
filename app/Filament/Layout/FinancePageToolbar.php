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
            default => $config,
        };
    }
}
