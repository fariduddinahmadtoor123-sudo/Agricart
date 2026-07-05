<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Reports\OverviewPage;

class ReportsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ReportsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Reports';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'reports';
    }
}
