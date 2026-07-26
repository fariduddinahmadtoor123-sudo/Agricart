<?php

namespace App\Filament\Clusters\Store;

use App\Filament\Clusters\StoreCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class AboutPage extends BaseModulePage
{
    protected static ?string $cluster = StoreCluster::class;

    protected static ?string $navigationLabel = 'About';

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'about';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected string $view = 'filament.pages.store.about';

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
