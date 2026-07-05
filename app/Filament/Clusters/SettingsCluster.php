<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SettingsCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $clusterBreadcrumb = 'Settings';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 14;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
}
