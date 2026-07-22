<?php

namespace App\Filament\Clusters\Logistics;

use App\Filament\Clusters\LogisticsCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class DeliveryMethodsPage extends BaseModulePage
{
    protected static ?string $cluster = LogisticsCluster::class;

    protected static ?string $navigationLabel = 'Delivery Methods';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'delivery-methods';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected string $view = 'filament.pages.logistics.delivery-methods';

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
