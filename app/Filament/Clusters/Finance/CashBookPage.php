<?php

namespace App\Filament\Clusters\Finance;

use App\Filament\Clusters\FinanceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CashBookPage extends BasePlaceholderPage
{
    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Cash Book';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'cash-book';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
}
