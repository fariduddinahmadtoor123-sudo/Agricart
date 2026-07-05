<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class LogisticsCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Logistics';

    protected static ?string $clusterBreadcrumb = 'Logistics';

    protected static ?string $slug = 'logistics';

    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;
}
