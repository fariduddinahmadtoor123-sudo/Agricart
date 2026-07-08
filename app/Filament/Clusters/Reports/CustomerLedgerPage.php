<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BaseReportModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CustomerLedgerPage extends BaseReportModulePage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'Cust Ledger';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'customer-ledger';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function reportKey(): string
    {
        return 'customer-ledger';
    }
}
