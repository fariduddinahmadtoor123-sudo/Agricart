<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PermissionPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Permission';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'permission';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
}
