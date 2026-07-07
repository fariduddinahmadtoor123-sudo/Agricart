<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class MarketplaceCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Pricing & Controls';

    protected static ?string $clusterBreadcrumb = 'Pricing & Controls';

    protected static ?string $slug = 'marketplace';

    protected static ?int $navigationSort = 13;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;
}
