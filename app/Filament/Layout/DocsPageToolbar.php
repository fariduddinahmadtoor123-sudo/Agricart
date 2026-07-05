<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Docs\OverviewPage;

class DocsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return DocsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Docs';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'docs';
    }
}
