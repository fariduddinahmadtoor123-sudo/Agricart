<?php

namespace App\Filament\Clusters\Marketplace;

use App\Filament\Clusters\MarketplaceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PricingRulesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = MarketplaceCluster::class;

    protected static ?string $navigationLabel = 'Pricing';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'pricing-rules';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
}
