<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class UsersPage extends BasePlaceholderPage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
}
