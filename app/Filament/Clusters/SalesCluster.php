<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SalesCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Sales';

    protected static ?string $clusterBreadcrumb = 'Sales';

    protected static ?string $slug = 'sales';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;
}
