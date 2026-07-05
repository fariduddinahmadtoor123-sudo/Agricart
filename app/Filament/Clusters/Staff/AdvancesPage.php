<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AdvancesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Advances';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'advances';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
}
