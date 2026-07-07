<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use BackedEnum;
use App\Filament\Pages\BaseModulePage;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class BrandsPage extends BaseModulePage
{

    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Brands';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'brands';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected string $view = 'filament.pages.catalog.brands';

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