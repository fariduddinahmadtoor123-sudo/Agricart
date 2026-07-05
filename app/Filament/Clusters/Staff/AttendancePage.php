<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AttendancePage extends BasePlaceholderPage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Attendance';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'attendance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
}
