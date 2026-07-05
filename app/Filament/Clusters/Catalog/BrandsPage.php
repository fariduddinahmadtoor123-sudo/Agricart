<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class BrandsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Brands';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'brands';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
}
