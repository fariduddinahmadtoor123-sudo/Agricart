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

    /**
     * @return array<string, mixed>|null
     */
    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        return [
            ...$config,
            'addLabel' => null,
            'hideSearch' => true,
            'hideFilters' => true,
            'hideColumns' => true,
        ];
    }
}
