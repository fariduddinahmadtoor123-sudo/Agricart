<?php

namespace App\Filament\Clusters\Store;

use App\Filament\Clusters\StoreCluster;
use BackedEnum;
use App\Filament\Pages\BaseModulePage;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class TickerPage extends BaseModulePage
{

    protected static ?string $cluster = StoreCluster::class;

    protected static ?string $navigationLabel = 'Ticker';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'ticker';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.store.ticker';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return StoreCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}