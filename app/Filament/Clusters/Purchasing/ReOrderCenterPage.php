<?php

namespace App\Filament\Clusters\Purchasing;

use App\Filament\Clusters\PurchasingCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ReOrderCenterPage extends BasePlaceholderPage
{
    protected static ?string $cluster = PurchasingCluster::class;

    protected static ?string $navigationLabel = 'Re-Order';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 're-order-center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;
}
