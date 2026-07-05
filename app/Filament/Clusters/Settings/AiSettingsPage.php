<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AiSettingsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'AI';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'ai-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;
}
