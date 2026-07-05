<?php

namespace App\Filament\Clusters\Finance;

use App\Filament\Clusters\FinanceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class BankAccountsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Banks';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'bank-accounts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
}
