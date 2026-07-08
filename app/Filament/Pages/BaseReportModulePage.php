<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\ReportsCluster;
use Illuminate\Contracts\Support\Htmlable;

abstract class BaseReportModulePage extends BaseModulePage
{
    protected string $view = 'filament.pages.reports.report-shell-page';

    abstract public static function reportKey(): string;

    public function getReportKey(): string
    {
        return static::reportKey();
    }

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
        return ReportsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
