<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Clusters\Store\OverviewPage;
use BackedEnum;
use App\Filament\Pages\BaseModulePage;
use Filament\Support\Icons\Heroicon;

class StoreSettingPage extends BaseModulePage
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