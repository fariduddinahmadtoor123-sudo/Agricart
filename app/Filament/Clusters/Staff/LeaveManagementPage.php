<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class LeaveManagementPage extends BasePlaceholderPage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Leave';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'leave-management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
}
