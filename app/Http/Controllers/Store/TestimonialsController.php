<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Store\CompanyTestimonialService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StorefrontSchemaService;
use Illuminate\Contracts\View\View;

class TestimonialsController extends Controller
{
    public function index(
        StoreLayoutService $layout,
        CompanyTestimonialService $testimonials,
        StorefrontSchemaService $schema,
    ): View {
        $summary = $testimonials->publicSummary();
        $reviews = $testimonials->publicReviews(100);
        $customer = auth('customer')->user();
        $myReview = $customer instanceof Customer
            ? $testimonials->existingForCustomer($customer)
            : null;

        return view('store.testimonials.index', [
            ...$layout->layoutData(),
            'summary' => $summary,
            'testimonials' => $reviews,
            'myReview' => $myReview,
            'schemaDocument' => $schema->testimonialsDocument($summary),
        ]);
    }
}
