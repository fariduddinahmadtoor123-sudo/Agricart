<?php

namespace App\Filament\Clusters\Store;

use App\Filament\Clusters\StoreCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ItemsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = StoreCluster::class;

    protected static ?string $navigationLabel = 'Items';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'items';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;
}
