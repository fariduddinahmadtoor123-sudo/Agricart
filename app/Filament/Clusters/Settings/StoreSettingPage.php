<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Clusters\Store\OverviewPage;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class StoreSettingPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Store';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'store-setting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static bool $shouldRegisterNavigation = true;

    public function mount(): void
    {
        $this->redirect(OverviewPage::getUrl());
    }
}
