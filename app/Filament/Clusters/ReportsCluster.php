<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ReportsCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $clusterBreadcrumb = 'Reports';

    protected static ?string $slug = 'reports';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
}
