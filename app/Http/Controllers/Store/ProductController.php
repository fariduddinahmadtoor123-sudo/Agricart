<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\SalesChannels\SalesChannelService;
use App\Services\Store\StoreCategoryService;
use App\Services\Store\StorefrontProductPresenter;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(string $slug, StoreLayoutService $layout, StoreProductService $products, StoreCategoryService $categories, StorefrontProductPresenter $presenter, SalesChannelService $channels): View
    {
        $product = $products->findActiveBySlug($slug);

        if ($product === null) {
            abort(404);
        }

        $defaultQty = 1.0;
        $buyPrice = $channels->onlineStoreShowsPrices()
            ? $presenter->priceForQuantity($product, $defaultQty)
            : null;
        $priceTable = $channels->onlineStoreShowsPrices()
            ? $presenter->quantityPriceTable($product)
            : [];

        return view('store.product.show', [
            ...$layout->layoutData(),
            'product' => $product,
            'productService' => $products,
            'presenter' => $presenter,
            'categoryService' => $categories,
            'imageUrls' => $products->allImageUrls($product),
            'buyPrice' => $buyPrice,
            'priceTable' => $priceTable,
            'identityFacts' => $presenter->identityFacts($product),
            'contentSections' => $presenter->contentSections($product),
        ]);
    }
}
