<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class MarketplaceCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Marketplace';

    protected static ?string $clusterBreadcrumb = 'Marketplace';

    protected static ?string $slug = 'marketplace';

    protected static ?int $navigationSort = 13;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAmericas;
}
