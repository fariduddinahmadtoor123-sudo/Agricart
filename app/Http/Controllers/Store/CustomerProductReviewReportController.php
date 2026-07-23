<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductReview;
use App\Models\ProductReviewReport;
use App\Services\Store\ProductReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerProductReviewReportController extends Controller
{
    public function store(
        Request $request,
        int $review,
        ProductReviewService $reviews,
    ): RedirectResponse {
        $customer = $request->user('customer');

        if (! $customer instanceof Customer) {
            abort(403);
        }

        $model = ProductReview::query()
            ->whereKey($review)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->with('product:id,slug')
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::in(ProductReviewReport::REASONS)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $reviews->report(
                $model,
                $customer,
                (string) $validated['reason'],
                $validated['note'] ?? null,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $slug = $model->product?->slug;

        if (! filled($slug)) {
            return redirect()
                ->route('store.home')
                ->with('customer_auth_status', __('agricart.storefront.reviews.report_thanks'));
        }

        return redirect()
            ->route('store.product', $slug)
            ->with('customer_auth_status', __('agricart.storefront.reviews.report_thanks'));
    }
}
