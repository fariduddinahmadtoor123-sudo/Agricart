<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SalesReturnsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'Returns';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'sales-returns';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;
}
