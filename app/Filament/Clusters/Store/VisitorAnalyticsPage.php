<?php

namespace App\Filament\Clusters\Store;

use App\Filament\Clusters\StoreCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class VisitorAnalyticsPage extends BaseModulePage
{
    protected static ?string $cluster = StoreCluster::class;

    protected static ?string $navigationLabel = 'Visitor Analytics';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'visitor-analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.store.visitor-analytics';

    protected static function requiredPermission(): ?string
    {
        return 'store.analytics.view';
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
        return StoreCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
