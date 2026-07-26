<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\CompanyTestimonial;
use App\Models\Customer;
use App\Services\Store\CompanyTestimonialService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerCompanyTestimonialController extends Controller
{
    public function create(
        Request $request,
        StoreLayoutService $layout,
        CompanyTestimonialService $testimonials,
    ): View {
        $customer = $this->customer($request);
        $existing = $testimonials->existingForCustomer($customer);

        return view('store.testimonials.create', [
            ...$layout->layoutData(),
            'existing' => $existing,
            'isEditing' => $existing !== null,
        ]);
    }

    public function store(
        Request $request,
        CompanyTestimonialService $testimonials,
    ): RedirectResponse {
        $customer = $this->customer($request);
        $existing = $testimonials->existingForCustomer($customer);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:'.CompanyTestimonial::MAX_TITLE_LENGTH],
            'body' => ['required', 'string', 'min:10', 'max:'.CompanyTestimonial::MAX_BODY_LENGTH],
        ], [
            'rating.required' => __('agricart.storefront.testimonials.errors.rating'),
        ]);

        $testimonials->submit(
            $customer,
            (int) $validated['rating'],
            $validated['body'],
            $validated['title'] ?? null,
        );

        $message = $existing !== null
            ? __('agricart.storefront.testimonials.updated')
            : __('agricart.storefront.testimonials.submitted');

        return redirect()
            ->route('store.testimonials')
            ->with('customer_auth_status', $message);
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
