<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class HomeExpensesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Home Expenses';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'home-expenses';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
}
