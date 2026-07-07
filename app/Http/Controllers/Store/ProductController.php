<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreCategoryService;
use App\Services\Store\StorefrontProductPresenter;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(string $slug, StoreLayoutService $layout, StoreProductService $products, StoreCategoryService $categories, StorefrontProductPresenter $presenter): View
    {
        $product = $products->findActiveBySlug($slug);

        if ($product === null) {
            abort(404);
        }

        $defaultQty = 1.0;
        $buyPrice = $presenter->priceForQuantity($product, $defaultQty);

        return view('store.product.show', [
            ...$layout->layoutData(),
            'product' => $product,
            'productService' => $products,
            'presenter' => $presenter,
            'categoryService' => $categories,
            'imageUrls' => $products->allImageUrls($product),
            'buyPrice' => $buyPrice,
            'priceTable' => $presenter->quantityPriceTable($product),
            'identityFacts' => $presenter->identityFacts($product),
            'contentSections' => $presenter->contentSections($product),
        ]);
    }
}
