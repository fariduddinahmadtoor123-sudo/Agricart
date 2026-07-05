<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class StaffReportsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'staff-reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;
}
