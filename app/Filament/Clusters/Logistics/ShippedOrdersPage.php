<?php

namespace App\Filament\Clusters\Logistics;

use App\Filament\Clusters\LogisticsCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ShippedOrdersPage extends BaseModulePage
{
    protected static ?string $cluster = LogisticsCluster::class;

    protected static ?string $navigationLabel = 'Shipped';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'shipped';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected string $view = 'filament.pages.logistics.shipped';

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
        return LogisticsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
