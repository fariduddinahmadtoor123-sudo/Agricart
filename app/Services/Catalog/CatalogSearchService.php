<?php

namespace App\Services\Catalog;

use App\Filament\Clusters\Catalog\AttributesPage;
use App\Filament\Clusters\Catalog\BrandsPage;
use App\Filament\Clusters\Catalog\CategoriesPage;
use App\Filament\Clusters\Catalog\ControlsPage;
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
use App\Support\Search\SearchHit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatalogSearchService
{
    /**
     * @return array<string, list<array{label: string, meta: string|null, url: string, image: string|null, panel: array<string, mixed>|null}>>
     */
    public function search(string $term, int $limitPerGroup = 6): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%' . $term . '%';

        return array_filter([
            'products' => $this->mapResults(
                Product::query()
                    ->where(function (Builder $query) use ($needle): void {
                        $query->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('code', 'like', $needle)
                            ->orWhere('sku', 'like', $needle)
                            ->orWhere('slug', 'like', $needle)
                            ->orWhere('label_code', 'like', $needle);
                    })
                    ->orderBy('name_en')
                    ->limit($limitPerGroup)
                    ->get(),
                ProductsPage::getUrl(),
                fn (Product $product): ?string => $product->code ?: $product->sku,
                fn (Product $product, string $label): array => [
                    'image' => SearchHit::image($product->image_path),
                    'panel' => [
                        'formKey' => 'products',
                        'productId' => $product->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.catalog.products'),
                    ],
                ],
            ),
            'categories' => $this->mapResults(
                Category::query()
                    ->where(function (Builder $query) use ($needle): void {
                        $query->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('code', 'like', $needle)
                            ->orWhere('slug', 'like', $needle);
                    })
                    ->orderBy('name_en')
                    ->limit($limitPerGroup)
                    ->get(),
                CategoriesPage::getUrl(),
                fn (Category $category): ?string => $category->code ?: $category->slug,
                fn (Category $category, string $label): array => [
                    'image' => SearchHit::image($category->image_path),
                    'panel' => [
                        'formKey' => 'categories',
                        'categoryId' => $category->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.catalog.categories'),
                    ],
                ],
            ),
            'brands' => $this->mapResults(
                Brand::query()
                    ->where(function (Builder $query) use ($needle): void {
                        $query->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('code', 'like', $needle);
                    })
                    ->orderBy('name_en')
                    ->limit($limitPerGroup)
                    ->get(),
                BrandsPage::getUrl(),
                fn (Brand $brand): ?string => $brand->code,
                fn (Brand $brand, string $label): array => [
                    'image' => SearchHit::image($brand->image_path),
                    'panel' => [
                        'formKey' => 'brands',
                        'brandId' => $brand->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.catalog.brands'),
                    ],
                ],
            ),
            'units' => $this->mapResults(
                Unit::query()
                    ->where(function (Builder $query) use ($needle): void {
                        $query->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('code', 'like', $needle)
                            ->orWhere('abbreviation_en', 'like', $needle)
                            ->orWhere('abbreviation_ur', 'like', $needle);
                    })
                    ->orderBy('name_en')
                    ->limit($limitPerGroup)
                    ->get(),
                UnitsPage::getUrl(),
                fn (Unit $unit): ?string => $unit->code,
                fn (Unit $unit, string $label): array => [
                    'panel' => [
                        'formKey' => 'units',
                        'unitId' => $unit->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.catalog.units'),
                    ],
                ],
            ),
            'packing_units' => $this->mapResults(
                PackingUnit::query()
                    ->where(function (Builder $query) use ($needle): void {
                        $query->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('code', 'like', $needle);
                    })
                    ->orderBy('name_en')
                    ->limit($limitPerGroup)
                    ->get(),
                PackingUnitsPage::getUrl(),
                fn (PackingUnit $packingUnit): ?string => $packingUnit->code,
                fn (PackingUnit $packingUnit, string $label): array => [
                    'panel' => [
                        'formKey' => 'packing-units',
                        'packingUnitId' => $packingUnit->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.catalog.packing_units'),
                    ],
                ],
            ),
            'attributes' => $this->mapResults(
                CatalogAttribute::query()
                    ->where(function (Builder $query) use ($needle): void {
                        $query->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('code', 'like', $needle);
                    })
                    ->orderBy('name_en')
                    ->limit($limitPerGroup)
                    ->get(),
                AttributesPage::getUrl(),
                fn (CatalogAttribute $attribute): ?string => $attribute->code,
                fn (CatalogAttribute $attribute, string $label): array => [
                    'panel' => [
                        'formKey' => 'attributes',
                        'attributeId' => $attribute->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.catalog.attributes'),
                    ],
                ],
            ),
            'controls' => $this->mapResults(
                CatalogControl::query()
                    ->where(function (Builder $query) use ($needle): void {
                        $query->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('code', 'like', $needle)
                            ->orWhere('value_en', 'like', $needle)
                            ->orWhere('value_ur', 'like', $needle);
                    })
                    ->orderBy('name_en')
                    ->limit($limitPerGroup)
                    ->get(),
                ControlsPage::getUrl(),
                fn (CatalogControl $control): ?string => $control->code,
                fn (CatalogControl $control, string $label): array => [
                    'panel' => [
                        'formKey' => 'controls',
                        'controlId' => $control->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.catalog.controls'),
                    ],
                ],
            ),
        ], fn (array $group): bool => $group !== []);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Model>  $records
     * @param  callable(Model): (?string)  $metaResolver
     * @param  callable(Model, string): array{image?: ?string, panel?: ?array<string, mixed>}  $decorate
     * @return list<array{label: string, meta: string|null, url: string, image: string|null, panel: array<string, mixed>|null}>
     */
    private function mapResults($records, string $url, callable $metaResolver, callable $decorate): array
    {
        return $records
            ->map(function (Model $record) use ($url, $metaResolver, $decorate): array {
                $label = filled($record->name_en ?? null)
                    ? (string) $record->name_en
                    : (string) ($record->name_ur ?? __('agricart.catalog.overview.search_untitled'));

                $meta = $metaResolver($record);
                $extra = $decorate($record, $label);

                return SearchHit::make(
                    $label,
                    filled($meta) ? (string) $meta : null,
                    $url,
                    $extra['image'] ?? null,
                    $extra['panel'] ?? null,
                );
            })
            ->values()
            ->all();
    }
}
