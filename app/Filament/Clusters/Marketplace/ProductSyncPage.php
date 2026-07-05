<?php

namespace App\Filament\Clusters\Marketplace;

use App\Filament\Clusters\MarketplaceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ProductSyncPage extends BasePlaceholderPage
{
    protected static ?string $cluster = MarketplaceCluster::class;

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'product-sync';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;
}
