<?php

namespace App\Filament\Clusters;

use App\Filament\Clusters\Catalog\OverviewPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CatalogCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Catalog';

    protected static ?string $clusterBreadcrumb = 'Catalog';

    protected static ?string $slug = 'catalog';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
}
