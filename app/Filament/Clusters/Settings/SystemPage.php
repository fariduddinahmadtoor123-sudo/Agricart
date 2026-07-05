<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SystemPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'System';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'system';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;
}
