<?php

namespace App\Filament\Clusters;

use App\Filament\Clusters\FxLearn\TradeTerminalPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class FxLearnCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'FX Learn';

    protected static ?string $clusterBreadcrumb = 'FX Learn';

    protected static ?string $slug = 'fx-learn';

    protected static ?int $navigationSort = 17;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    public static function getNavigationUrl(): string
    {
        return TradeTerminalPage::getUrl();
    }

    public function mount(): void
    {
        redirect(TradeTerminalPage::getUrl());
    }
}
