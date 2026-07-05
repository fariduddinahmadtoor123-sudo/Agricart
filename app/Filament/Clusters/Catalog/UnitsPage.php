<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class UnitsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Units';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'units';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;
}
