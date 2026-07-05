<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class HeldSalesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'Held';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'held-sales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPauseCircle;
}
