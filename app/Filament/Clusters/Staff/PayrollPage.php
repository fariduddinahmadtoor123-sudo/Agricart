<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PayrollPage extends BasePlaceholderPage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Payroll';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;
}
