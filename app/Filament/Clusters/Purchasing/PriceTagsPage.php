<?php

namespace App\Filament\Clusters\Purchasing;

use App\Filament\Clusters\PurchasingCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PriceTagsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = PurchasingCluster::class;

    protected static ?string $navigationLabel = 'Tags';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'price-tags';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
}
