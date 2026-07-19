<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Store\StoreCategoryService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function show(string $slug, StoreLayoutService $layout, StoreCategoryService $categories, StoreProductService $products, AnalyticsContext $analytics): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $analytics->setPage('category', $category->id, $category->slug);

        $childCategories = $categories->childCategories($category);
        $categoryProducts = $products->paginatedProductsForCategory($category);

        return view('store.category.show', [
            ...$layout->layoutData(),
            'category' => $category,
            'childCategories' => $childCategories,
            'products' => $categoryProducts,
            'contentBlocks' => $categories->storefrontContentBlocks($category),
            'faqs' => $categories->localizedFaqs($category),
            'hasStorefrontContent' => $categories->hasStorefrontContent($category),
            'categoryService' => $categories,
            'productService' => $products,
        ]);
    }
}
