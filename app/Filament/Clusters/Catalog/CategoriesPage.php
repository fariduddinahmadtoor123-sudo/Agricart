<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use App\Filament\Concerns\HasTranslatedNavigationLabel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class CategoriesPage extends Page
{
    use HasTranslatedNavigationLabel;

    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Categories';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'categories';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected string $view = 'filament.pages.catalog.categories';

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
        return CatalogCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
