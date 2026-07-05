<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasTranslatedNavigationLabel;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

abstract class BasePlaceholderPage extends Page
{
    use HasTranslatedNavigationLabel;

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
        return __('agricart.placeholder.message');
    }
}
