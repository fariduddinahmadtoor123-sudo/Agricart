<?php

namespace App\Filament\Clusters\Dashboard;

use App\Filament\Clusters\DashboardCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class SummaryPage extends BaseModulePage
{
    protected static ?string $cluster = DashboardCluster::class;

    protected static ?string $navigationLabel = 'Summary';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'summary';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected string $view = 'filament.pages.dashboard.summary';

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
        return DashboardCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
