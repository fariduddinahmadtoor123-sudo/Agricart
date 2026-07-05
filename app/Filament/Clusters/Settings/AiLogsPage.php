<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AiLogsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Logs';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'ai-logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;
}
