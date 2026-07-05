<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class StaffPage extends BasePlaceholderPage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Staff';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
}
