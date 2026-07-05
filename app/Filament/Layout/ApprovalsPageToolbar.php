<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Approvals\OverviewPage;

class ApprovalsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ApprovalsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Approvals';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'approvals';
    }
}
