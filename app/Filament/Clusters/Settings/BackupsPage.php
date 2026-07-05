<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class BackupsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Backups';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'backups';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;
}
