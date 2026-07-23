<?php

namespace App\Filament\Clusters\Store;

use App\Filament\Clusters\StoreCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ReviewsPage extends BaseModulePage
{
    protected static ?string $cluster = StoreCluster::class;

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'reviews';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected string $view = 'filament.pages.store.reviews';

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
