<?php

namespace App\Filament\Clusters\Purchasing;

use App\Filament\Clusters\PurchasingCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PurchaseQuotationsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = PurchasingCluster::class;

    protected static ?string $navigationLabel = 'Quotes';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'purchase-quotations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;
}
