<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class DashboardCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $clusterBreadcrumb = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
}
