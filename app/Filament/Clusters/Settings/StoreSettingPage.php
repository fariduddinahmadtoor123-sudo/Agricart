<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class StoreSettingPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Store';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'store-setting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
}
