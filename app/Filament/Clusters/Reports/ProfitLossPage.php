<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BaseReportModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ProfitLossPage extends BaseReportModulePage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'P&L';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'profit-loss';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    public static function reportKey(): string
    {
        return 'profit-loss';
    }
}
