<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ServiceCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Service';

    protected static ?string $clusterBreadcrumb = 'Service';

    protected static ?string $slug = 'service';

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;
}
