<?php

namespace App\Filament\Clusters\Store;

use App\Filament\Clusters\StoreCluster;
use App\Filament\Concerns\HasTranslatedNavigationLabel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class HeaderPage extends Page
{
    use HasTranslatedNavigationLabel;

    protected static ?string $cluster = StoreCluster::class;

    protected static ?string $navigationLabel = 'Header';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'header';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.store.header';

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
