<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class OverviewPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
}
