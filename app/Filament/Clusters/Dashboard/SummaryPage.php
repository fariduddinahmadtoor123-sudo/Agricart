<?php

namespace App\Filament\Clusters\Dashboard;

use App\Filament\Clusters\DashboardCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SummaryPage extends BasePlaceholderPage
{
    protected static ?string $cluster = DashboardCluster::class;

    protected static ?string $navigationLabel = 'Summary';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'summary';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;
}
