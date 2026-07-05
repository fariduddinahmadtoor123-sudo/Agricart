<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SalesQuotationsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'Quotes';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'sales-quotations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
}
