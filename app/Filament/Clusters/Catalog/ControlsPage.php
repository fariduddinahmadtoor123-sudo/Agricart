<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ControlsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Controls';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'controls';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
}
