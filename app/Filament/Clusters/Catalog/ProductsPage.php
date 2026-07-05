<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ProductsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
}
