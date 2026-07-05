<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PrintingPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Print';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'printing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;
}
