<?php

namespace App\Filament\Clusters\Marketplace;

use App\Filament\Clusters\MarketplaceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class OrdersSyncPage extends BasePlaceholderPage
{
    protected static ?string $cluster = MarketplaceCluster::class;

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'orders-sync';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;
}
