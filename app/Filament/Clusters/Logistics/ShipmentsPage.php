<?php

namespace App\Filament\Clusters\Logistics;

use App\Filament\Clusters\LogisticsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ShipmentsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = LogisticsCluster::class;

    protected static ?string $navigationLabel = 'Shipments';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'shipments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
}
