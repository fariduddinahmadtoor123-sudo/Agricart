<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductReview;
use App\Services\Store\ProductReviewService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerProductReviewController extends Controller
{
    public function create(
        Request $request,
        string $slug,
        StoreLayoutService $layout,
        StoreProductService $products,
        ProductReviewService $reviews,
    ): View|RedirectResponse {
        $customer = $this->customer($request);
        $product = $products->findActiveBySlug($slug);

        if ($product === null) {
            abort(404);
        }

        $existing = $reviews->existingReview($customer, $product);

        if ($existing?->status === ProductReview::STATUS_APPROVED) {
            return redirect()
                ->route('store.product', $product->slug)
                ->with('customer_auth_status', __('agricart.storefront.reviews.errors.already_approved'));
        }

        $submissionPath = $reviews->resolveSubmissionPath($customer, $product);
        $requiresProof = $submissionPath === ProductReview::PATH_PROOF;

        return view('store.reviews.create', [
            ...$layout->layoutData(),
            'product' => $product,
            'productService' => $products,
            'existing' => $existing?->load(['galleryPhotos', 'proofPhotos']),
            'submissionPath' => $submissionPath,
            'requiresProof' => $requiresProof,
            'maxPhotos' => ProductReviewService::MAX_PHOTOS,
            'maxProofs' => ProductReviewService::MAX_PROOFS,
        ]);
    }

    public function store(
        Request $request,
        string $slug,
        StoreProductService $products,
        ProductReviewService $reviews,
    ): RedirectResponse {
        $customer = $this->customer($request);
        $product = $products->findActiveBySlug($slug);

        if ($product === null) {
            abort(404);
        }

        $requiresProof = $reviews->resolveSubmissionPath($customer, $product) === ProductReview::PATH_PROOF;
        $existing = $reviews->existingReview($customer, $product);
        $hasExistingProof = $existing !== null && $existing->proofPhotos()->exists();
        $proofsRules = $requiresProof && ! $hasExistingProof
            ? ['required', 'array', 'min:1', 'max:'.ProductReviewService::MAX_PROOFS]
            : ['nullable', 'array', 'max:'.ProductReviewService::MAX_PROOFS];

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:'.ProductReviewService::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.ProductReviewService::MAX_PHOTO_KB],
            'proofs' => $proofsRules,
            'proofs.*' => ['file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:'.ProductReviewService::MAX_PROOF_KB],
            'proof_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $reviews->submit(
                $customer,
                $product,
                (int) $validated['rating'],
                (string) $validated['body'],
                array_values($request->file('photos', []) ?: []),
                array_values($request->file('proofs', []) ?: []),
                isset($validated['proof_note']) ? (string) $validated['proof_note'] : null,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return redirect()
            ->route('store.product', $product->slug)
            ->with('customer_auth_status', __('agricart.storefront.reviews.submitted'));
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
