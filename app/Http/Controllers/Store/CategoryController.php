<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Store\StoreCategoryService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function show(string $slug, StoreLayoutService $layout, StoreCategoryService $categories): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return view('store.category.show', [
            ...$layout->layoutData(),
            'category' => $category,
            'childCategories' => $categories->childCategories($category),
            'contentBlocks' => $categories->storefrontContentBlocks($category),
            'faqs' => $categories->localizedFaqs($category),
            'hasStorefrontContent' => $categories->hasStorefrontContent($category),
            'categoryService' => $categories,
        ]);
    }
}
