<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AttributesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Attributes';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'attributes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;
}
