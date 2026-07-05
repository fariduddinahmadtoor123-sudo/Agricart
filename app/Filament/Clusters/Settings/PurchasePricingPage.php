<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PurchasePricingPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Pricing';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'purchase-pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
}
