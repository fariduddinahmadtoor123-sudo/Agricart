<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Contracts\Support\Htmlable;

abstract class BaseAgricartCluster extends Cluster
{
    protected static bool $shouldRegisterSubNavigation = false;

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => static::getNavigationLabel(),
        ];
    }

    public static function getNavigationUrl(): string
    {
        $overviewPage = static::resolveOverviewPageClass();

        if ($overviewPage !== null) {
            return $overviewPage::getUrl();
        }

        return parent::getNavigationUrl();
    }

    public function mount(): void
    {
        $overviewPage = static::resolveOverviewPageClass();

        if ($overviewPage !== null) {
            redirect($overviewPage::getUrl());

            return;
        }

        parent::mount();
    }

    /**
     * @return class-string|null
     */
    protected static function resolveOverviewPageClass(): ?string
    {
        $module = str(class_basename(static::class))->beforeLast('Cluster')->toString();
        $pageClass = "App\\Filament\\Clusters\\{$module}\\OverviewPage";

        return class_exists($pageClass) ? $pageClass : null;
    }
}
