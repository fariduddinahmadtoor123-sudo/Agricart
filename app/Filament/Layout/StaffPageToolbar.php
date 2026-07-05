<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Staff\OverviewPage;

class StaffPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return StaffSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Staff';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'staff';
    }
}
