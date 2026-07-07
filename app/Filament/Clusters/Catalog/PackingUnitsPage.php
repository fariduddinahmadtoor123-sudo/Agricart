<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use BackedEnum;
use App\Filament\Pages\BaseModulePage;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class PackingUnitsPage extends BaseModulePage
{

    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Packing Units';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'packing-units';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected string $view = 'filament.pages.catalog.packing-units';

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
        return CatalogCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}