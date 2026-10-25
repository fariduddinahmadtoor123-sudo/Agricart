<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PendingPaymentsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Pending Payments';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'pending-payments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
}
