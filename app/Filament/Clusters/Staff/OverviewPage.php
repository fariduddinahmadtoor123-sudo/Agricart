<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class OverviewPage extends BasePlaceholderPage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
}
