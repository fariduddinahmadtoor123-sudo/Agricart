<?php

namespace App\Filament\Clusters\Finance;

use App\Filament\Clusters\FinanceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ExpensesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Expenses';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'expenses';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;
}
