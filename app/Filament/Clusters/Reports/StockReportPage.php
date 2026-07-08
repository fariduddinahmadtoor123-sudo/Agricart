<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BaseReportModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class StockReportPage extends BaseReportModulePage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'Stock';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'stock-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function reportKey(): string
    {
        return 'stock-report';
    }
}
