<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Store\BrandCustomerReviewService;
use App\Services\Store\StoreBrandService;
use App\Services\Store\StorefrontSchemaService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;

class BrandController extends Controller
{
    public function index(
        StoreLayoutService $layout,
        StoreBrandService $brands,
        StorefrontSchemaService $schema,
        AnalyticsContext $analytics,
    ): View {
        $analytics->setPage('brands', null, 'brands');
        $items = $brands->listedBrands();

        return view('store.brands.index', [
            ...$layout->layoutData(),
            'brands' => $items,
            'brandService' => $brands,
            'schemaDocument' => $schema->brandsIndexDocument($items->count()),
        ]);
    }

    public function show(
        string $slug,
        StoreLayoutService $layout,
        StoreBrandService $brands,
        BrandCustomerReviewService $customerReviews,
        StorefrontSchemaService $schema,
        AnalyticsContext $analytics,
    ): View {
        $brand = $brands->findPublicBySlug($slug);
        $analytics->setPage('brand', $brand->id, $brand->slug);

        $customer = auth('customer')->user();
        $canWriteBrandReview = $customer instanceof Customer
            && $customerReviews->canSubmit($customer, $brand);

        return view('store.brands.show', [
            ...$layout->layoutData(),
            'brand' => $brand,
            'brandService' => $brands,
            'ratingSummary' => $brands->ratingSummary($brand),
            'hasReview' => $brands->hasAgricartReview($brand),
            'customerReviewSummary' => $customerReviews->publicSummary($brand),
            'customerReviews' => $customerReviews->publicReviews($brand),
            'canWriteBrandReview' => $canWriteBrandReview,
            'schemaDocument' => $schema->brandDocument($brand, $brands),
        ]);
    }
}
