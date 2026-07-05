<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class OnlineOrdersPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'online-orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;
}
