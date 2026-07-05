<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class StoreCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Store';

    protected static ?string $clusterBreadcrumb = 'Store';

    protected static ?string $slug = 'store';

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;
}
