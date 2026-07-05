<?php

namespace App\Filament\Clusters\Purchasing;

use App\Filament\Clusters\PurchasingCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PurchasesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = PurchasingCluster::class;

    protected static ?string $navigationLabel = 'Purchases';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'purchases';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;
}
