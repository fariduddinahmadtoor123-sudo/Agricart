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
}
