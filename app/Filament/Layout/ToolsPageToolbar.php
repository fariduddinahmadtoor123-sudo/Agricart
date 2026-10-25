<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Tools\OverviewPage;

class ToolsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ToolsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'External Apps';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'tools';
    }
}