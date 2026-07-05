<?php

namespace App\Filament\Clusters\Store;

use App\Filament\Clusters\StoreCluster;
use App\Filament\Concerns\HasTranslatedNavigationLabel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class TopBarPage extends Page
{
    use HasTranslatedNavigationLabel;

    protected static ?string $cluster = StoreCluster::class;

    protected static ?string $navigationLabel = 'Top Bar';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'top-bar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3CenterLeft;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.store.top-bar';

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
