<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BaseReportModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SeasonalSalesPage extends BaseReportModulePage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'Season Sales';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'seasonal-sales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    public static function reportKey(): string
    {
        return 'seasonal-sales';
    }
}
