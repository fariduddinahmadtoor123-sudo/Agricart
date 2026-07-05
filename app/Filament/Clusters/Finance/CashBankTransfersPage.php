<?php

namespace App\Filament\Clusters\Finance;

use App\Filament\Clusters\FinanceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CashBankTransfersPage extends BasePlaceholderPage
{
    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Transfers';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'cash-bank-transfers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
}
