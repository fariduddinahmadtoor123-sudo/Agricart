<?php

namespace App\Filament\Clusters\Finance;

use App\Filament\Clusters\FinanceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ExpenseCategoriesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Categories';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'expense-categories';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
}
