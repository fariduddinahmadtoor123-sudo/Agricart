<?php

namespace App\Filament\Clusters\Purchasing;

use App\Filament\Clusters\PurchasingCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PurchasePaymentSheetPage extends BasePlaceholderPage
{
    protected static ?string $cluster = PurchasingCluster::class;

    protected static ?string $navigationLabel = 'Payments';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'purchase-payment-sheet';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
}
