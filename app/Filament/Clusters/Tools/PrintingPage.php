<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PrintingPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Printing';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'printing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;
}
