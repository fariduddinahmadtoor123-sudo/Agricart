<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AnalyticsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;
}
