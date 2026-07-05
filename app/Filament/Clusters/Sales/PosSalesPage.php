<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PosSalesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'POS';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'pos-sales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;
}
