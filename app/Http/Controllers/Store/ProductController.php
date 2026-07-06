<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreCategoryService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(string $slug, StoreLayoutService $layout, StoreProductService $products, StoreCategoryService $categories): View
    {
        $product = $products->findActiveBySlug($slug);

        if ($product === null) {
            abort(404);
        }

        return view('store.product.show', [
            ...$layout->layoutData(),
            'product' => $product,
            'productService' => $products,
            'categoryService' => $categories,
            'imageUrls' => $products->allImageUrls($product),
        ]);
    }
}
