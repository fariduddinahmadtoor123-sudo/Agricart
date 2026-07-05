<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

abstract class BasePlaceholderPage extends Page
{
    protected string $view = 'filament.pages.placeholder';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        $current = [
            static::getUrl() => static::getNavigationLabel(),
        ];

        if (filled($cluster = static::getCluster())) {
            return $cluster::unshiftClusterBreadcrumbs($current);
        }

        return $current;
    }

    public function getPlaceholderMessage(): string
    {
        return 'This section is a placeholder. Content will be added in a future phase.';
    }
}
