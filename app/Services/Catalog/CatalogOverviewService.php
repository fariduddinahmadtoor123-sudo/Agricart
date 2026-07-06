<?php

namespace App\Services\Catalog;

use App\Filament\Clusters\Catalog\AttributesPage;
use App\Filament\Clusters\Catalog\BrandsPage;
use App\Filament\Clusters\Catalog\CategoriesPage;
use App\Filament\Clusters\Catalog\ControlsPage;
use App\Filament\Clusters\Catalog\LabelsPage;
use App\Filament\Clusters\Catalog\PackingUnitsPage;
use App\Filament\Clusters\Catalog\ProductsPage;
use App\Filament\Clusters\Catalog\UnitsPage;
use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\PackingUnit;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class CatalogOverviewService
{
    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        return [
            $this->modelCard('categories', Category::class, CategoriesPage::class),
            $this->modelCard('products', Product::class, ProductsPage::class),
            $this->modelCard('brands', Brand::class, BrandsPage::class),
            $this->modelCard('units', Unit::class, UnitsPage::class),
            $this->modelCard('packing_units', PackingUnit::class, PackingUnitsPage::class),
            $this->modelCard('attributes', CatalogAttribute::class, AttributesPage::class),
            $this->modelCard('controls', CatalogControl::class, ControlsPage::class),
            $this->labelsCard(),
        ];
    }

    public function totalRecords(): int
    {
        return array_sum(array_column($this->statCards(), 'count'));
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, count: int, active: int|null, url: string}
     */
    private function modelCard(string $key, string $modelClass, string $pageClass): array
    {
        $query = $modelClass::query();
        $count = (clone $query)->count();
        $active = $this->hasStatusColumn($modelClass)
            ? (clone $query)->where('status', 'active')->count()
            : null;

        return [
            'key' => $key,
            'label' => (string) __('agricart.catalog.overview.cards.' . $key),
            'count' => $count,
            'active' => $active,
            'url' => $pageClass::getUrl(),
        ];
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string}
     */
    private function labelsCard(): array
    {
        $count = Product::query()
            ->whereNotNull('label_code')
            ->where('label_code', '!=', '')
            ->count();

        return [
            'key' => 'labels',
            'label' => (string) __('agricart.catalog.overview.cards.labels'),
            'count' => $count,
            'active' => null,
            'url' => LabelsPage::getUrl(),
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function hasStatusColumn(string $modelClass): bool
    {
        /** @var Model $model */
        $model = new $modelClass;

        return in_array('status', $model->getFillable(), true)
            || array_key_exists('status', $model->getCasts());
    }
}
