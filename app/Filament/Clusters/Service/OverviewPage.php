<?php

namespace App\Filament\Clusters\Service;

use App\Filament\Clusters\ServiceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class OverviewPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ServiceCluster::class;

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
}
