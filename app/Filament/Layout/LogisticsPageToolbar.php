<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Logistics\OverviewPage;

class LogisticsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return LogisticsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Logistics';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'logistics';
    }
}
