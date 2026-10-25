<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PurchasePaymentSummaryPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Purchase Payment Summary';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'purchase-payment-summary';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
}
