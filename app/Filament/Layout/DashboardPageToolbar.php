<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Dashboard\OverviewPage;

class DashboardPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return DashboardSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Dashboard';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'dashboard';
    }
}
