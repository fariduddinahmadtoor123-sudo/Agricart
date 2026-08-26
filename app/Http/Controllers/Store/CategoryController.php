<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Store\StoreCategoryService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StorePackageService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryController extends Controller
{
    public function show(string $slug, StoreLayoutService $layout, StoreCategoryService $categories, StoreProductService $products, StorePackageService $packages, AnalyticsContext $analytics): View
    {
        $category = $categories->findStorefrontBySlug($slug);

        abort_if($category === null, 404);

        $analytics->setPage('category', $category->id, $category->slug);

        $comingSoon = $categories->isComingSoon($category);
        $childCategories = $categories->childCategories($category);
        $categoryPackages = $comingSoon
            ? collect()
            : $packages->packagesForCategory($category);
        $categoryProducts = $comingSoon
            ? new LengthAwarePaginator([], 0, 36, 1, [
                'path' => request()->url(),
                'pageName' => 'page',
            ])
            : $products->paginatedProductsForCategory($category);

        // Count for this page title + child cards — never mutate $childCategories.
        $countTargets = $childCategories->isNotEmpty()
            ? collect([$category])->concat($childCategories)->unique('id')->values()
            : collect([$category]);
        $productCounts = $comingSoon
            ? array_fill_keys($countTargets->pluck('id')->map(fn ($id) => (int) $id)->all(), 0)
            : $categories->storefrontProductCounts($countTargets);

        return view('store.category.show', [
            ...$layout->layoutData(),
            'category' => $category,
            'comingSoon' => $comingSoon,
            'childCategories' => $childCategories,
            'packages' => $categoryPackages,
            'products' => $categoryProducts,
            'contentBlocks' => $categories->storefrontContentBlocks($category),
            'faqs' => $categories->localizedFaqs($category),
            'hasStorefrontContent' => $categories->hasStorefrontContent($category),
            'categoryService' => $categories,
            'packageService' => $packages,
            'productService' => $products,
            'categoryProductCount' => (int) ($productCounts[$category->id] ?? 0),
            'productCounts' => $productCounts,
        ]);
    }
}
