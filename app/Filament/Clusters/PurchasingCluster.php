<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PurchasingCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Purchasing';

    protected static ?string $clusterBreadcrumb = 'Purchasing';

    protected static ?string $slug = 'purchasing';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
}
