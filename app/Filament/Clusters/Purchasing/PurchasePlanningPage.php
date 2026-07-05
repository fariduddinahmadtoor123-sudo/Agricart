<?php

namespace App\Filament\Clusters\Purchasing;

use App\Filament\Clusters\PurchasingCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PurchasePlanningPage extends BasePlaceholderPage
{
    protected static ?string $cluster = PurchasingCluster::class;

    protected static ?string $navigationLabel = 'Planning';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'purchase-planning';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
}
