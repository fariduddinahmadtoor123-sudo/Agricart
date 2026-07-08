<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BaseReportModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CashReportPage extends BaseReportModulePage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'Cash';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'cash-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function reportKey(): string
    {
        return 'cash-report';
    }
}
