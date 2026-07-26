<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Logistics\DeliveryMethodService;
use App\Services\SalesChannels\SalesChannelService;
use App\Models\Customer;
use App\Services\Store\ProductReviewService;
use App\Services\Store\StoreCategoryService;
use App\Services\Store\StorefrontProductPresenter;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(
        string $slug,
        StoreLayoutService $layout,
        StoreProductService $products,
        StoreCategoryService $categories,
        StorefrontProductPresenter $presenter,
        SalesChannelService $channels,
        AnalyticsContext $analytics,
        DeliveryMethodService $deliveryMethods,
        ProductReviewService $productReviews,
    ): View {
        $product = $products->findStorefrontBySlug($slug);

        if ($product === null) {
            abort(404);
        }

        $analytics->setPage('product', $product->id, $product->slug);

        $comingSoon = $products->isComingSoon($product);
        $defaultQty = 1.0;
        $buyPrice = (! $comingSoon && $channels->onlineStoreShowsPrices())
            ? $presenter->priceForQuantity($product, $defaultQty)
            : null;
        // Quantity pricing is catalog info — hide for Coming soon (no commerce).
        $priceTable = $comingSoon ? [] : $presenter->quantityPriceTable($product);

        $customer = auth('customer')->user();
        $canWriteReview = $customer instanceof Customer
            && $productReviews->canSubmit($customer, $product);

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
            'deliveryOptions' => $deliveryMethods->optionsForProductPage($product->id),
            'reviewSummary' => $productReviews->publicSummary($product),
            'publicReviews' => $productReviews->publicReviews($product),
            'canWriteReview' => $canWriteReview,
        ]);
    }
}
