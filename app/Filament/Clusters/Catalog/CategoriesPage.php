<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CategoriesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Categories';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'categories';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;
}
