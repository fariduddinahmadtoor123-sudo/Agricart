<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\BrandCustomerReview;
use App\Models\Customer;
use App\Services\Store\BrandCustomerReviewService;
use App\Services\Store\StoreBrandService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerBrandReviewController extends Controller
{
    public function create(
        Request $request,
        string $slug,
        StoreLayoutService $layout,
        StoreBrandService $brands,
        BrandCustomerReviewService $reviews,
    ): View|RedirectResponse {
        $customer = $this->customer($request);
        $brand = $brands->findPublicBySlug($slug);
        $existing = $reviews->existingReview($customer, $brand);

        if ($existing?->status === BrandCustomerReview::STATUS_APPROVED) {
            return redirect()
                ->route('store.brand', $brand->slug)
                ->with('customer_auth_status', __('agricart.storefront.brand_reviews.errors.already_approved'));
        }

        return view('store.brand-reviews.create', [
            ...$layout->layoutData(),
            'brand' => $brand,
            'brandService' => $brands,
            'existing' => $existing,
        ]);
    }

    public function store(
        Request $request,
        string $slug,
        StoreBrandService $brands,
        BrandCustomerReviewService $reviews,
    ): RedirectResponse {
        $customer = $this->customer($request);
        $brand = $brands->findPublicBySlug($slug);

        $reviews->submit($customer, $brand, $request->only(['rating', 'body']));

        return redirect()
            ->route('store.brand', $brand->slug)
            ->with('customer_auth_status', __('agricart.storefront.brand_reviews.submitted'));
    }

    private function customer(Request $request): Customer
    {
        $customer = $request->user('customer');

        if (! $customer instanceof Customer) {
            abort(403);
        }

        return $customer;
    }
}
