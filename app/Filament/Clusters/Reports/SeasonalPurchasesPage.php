<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BaseReportModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SeasonalPurchasesPage extends BaseReportModulePage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'Season Purch';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'seasonal-purchases';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    public static function reportKey(): string
    {
        return 'seasonal-purchases';
    }
}
