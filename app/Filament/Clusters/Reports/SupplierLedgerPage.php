<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BaseReportModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SupplierLedgerPage extends BaseReportModulePage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'Supp Ledger';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'supplier-ledger';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function reportKey(): string
    {
        return 'supplier-ledger';
    }
}
