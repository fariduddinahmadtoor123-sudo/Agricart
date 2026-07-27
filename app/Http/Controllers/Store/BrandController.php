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
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(
        Request $request,
        StoreLayoutService $layout,
        StoreBrandService $brands,
        BrandCustomerReviewService $customerReviews,
        StorefrontSchemaService $schema,
        AnalyticsContext $analytics,
    ): View {
        $analytics->setPage('brands', null, 'brands');
        $searchQuery = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        $items = $brands->listedBrands($searchQuery !== '' ? $searchQuery : null);

        return view('store.brands.index', [
            ...$layout->layoutData(),
            'brands' => $items,
            'brandService' => $brands,
            'searchQuery' => $searchQuery,
            'brandCustomerSummaries' => $customerReviews->publicSummariesForBrandIds(
                $items->getCollection()->pluck('id')->all(),
            ),
            'schemaDocument' => $schema->brandsIndexDocument($items->total()),
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
