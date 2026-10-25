<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ReOrderPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Re Order';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 're-order';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;
}