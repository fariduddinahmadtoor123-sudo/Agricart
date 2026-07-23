<?php

namespace App\Services\Store;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewPhoto;
use App\Models\ProductReviewReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductReviewService
{
    public const MAX_PHOTOS = 4;

    public const MAX_PROOFS = 4;

    public const MAX_PHOTO_KB = 2048;

    public const MAX_PROOF_KB = 4096;

    /**
     * Delivered order that includes this product for the customer (newest first).
     */
    public function qualifyingOrder(Customer $customer, Product $product): ?OnlineSaleOrder
    {
        return OnlineSaleOrder::query()
            ->where('customer_id', $customer->id)
            ->where('fulfillment_status', OnlineSaleOrder::FULFILLMENT_DELIVERED)
            ->where('order_status', '!=', OnlineSaleOrder::STATUS_CANCELLED)
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->with(['items' => fn ($query) => $query->where('product_id', $product->id)])
            ->latest('id')
            ->first();
    }

    public function existingReview(Customer $customer, Product $product): ?ProductReview
    {
        return ProductReview::query()
            ->where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->first();
    }

    public function resolveSubmissionPath(Customer $customer, Product $product): string
    {
        return $this->qualifyingOrder($customer, $product) !== null
            ? ProductReview::PATH_VERIFIED_PURCHASE
            : ProductReview::PATH_PROOF;
    }

    public function canSubmit(Customer $customer, Product $product): bool
    {
        $existing = $this->existingReview($customer, $product);

        if ($existing === null) {
            return true;
        }

        return in_array($existing->status, [
            ProductReview::STATUS_PENDING,
            ProductReview::STATUS_REJECTED,
            ProductReview::STATUS_HIDDEN,
        ], true);
    }

    /**
     * @return array{average: float|null, count: int}
     */
    public function publicSummary(Product $product): array
    {
        $query = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('status', ProductReview::STATUS_APPROVED);

        $count = (int) $query->count();

        if ($count === 0) {
            return ['average' => null, 'count' => 0];
        }

        $average = (float) ProductReview::query()
            ->where('product_id', $product->id)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->avg('rating');

        return [
            'average' => round($average, 1),
            'count' => $count,
        ];
    }

    /**
     * @return Collection<int, ProductReview>
     */
    public function publicReviews(Product $product, int $limit = 50): Collection
    {
        return ProductReview::query()
            ->where('product_id', $product->id)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->with([
                'customer',
                'photos' => fn ($query) => $query->where('kind', ProductReviewPhoto::KIND_GALLERY),
            ])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  list<UploadedFile>  $galleryPhotos
     * @param  list<UploadedFile>  $proofFiles
     */
    public function submit(
        Customer $customer,
        Product $product,
        int $rating,
        string $body,
        array $galleryPhotos = [],
        array $proofFiles = [],
        ?string $proofNote = null,
    ): ProductReview {
        if (! $this->canSubmit($customer, $product)) {
            throw ValidationException::withMessages([
                'review' => [__('agricart.storefront.reviews.errors.already_approved')],
            ]);
        }

        $order = $this->qualifyingOrder($customer, $product);
        $path = $order !== null
            ? ProductReview::PATH_VERIFIED_PURCHASE
            : ProductReview::PATH_PROOF;

        $rating = max(ProductReview::MIN_RATING, min(ProductReview::MAX_RATING, $rating));
        $body = trim($body);
        $proofNote = $proofNote !== null ? trim($proofNote) : '';

        if ($body === '' || mb_strlen($body) < 10 || mb_strlen($body) > ProductReview::MAX_BODY_LENGTH) {
            throw ValidationException::withMessages([
                'body' => [__('agricart.storefront.reviews.errors.body')],
            ]);
        }

        if (count($galleryPhotos) > self::MAX_PHOTOS) {
            throw ValidationException::withMessages([
                'photos' => [__('agricart.storefront.reviews.errors.photos')],
            ]);
        }

        if (count($proofFiles) > self::MAX_PROOFS) {
            throw ValidationException::withMessages([
                'proofs' => [__('agricart.storefront.reviews.errors.proofs')],
            ]);
        }

        $existing = $this->existingReview($customer, $product);
        $existingProofCount = $existing
            ? $existing->proofPhotos()->count()
            : 0;

        if ($path === ProductReview::PATH_PROOF && $proofFiles === [] && $existingProofCount === 0) {
            throw ValidationException::withMessages([
                'proofs' => [__('agricart.storefront.reviews.errors.proof_required')],
            ]);
        }

        if ($proofNote !== '' && mb_strlen($proofNote) > 500) {
            throw ValidationException::withMessages([
                'proof_note' => [__('agricart.storefront.reviews.errors.proof_note')],
            ]);
        }

        $item = $order?->items->firstWhere('product_id', $product->id);

        return DB::transaction(function () use (
            $customer,
            $product,
            $order,
            $item,
            $existing,
            $rating,
            $body,
            $path,
            $proofNote,
            $galleryPhotos,
            $proofFiles,
        ): ProductReview {
            $payload = [
                'product_id' => $product->id,
                'customer_id' => $customer->id,
                'online_sale_order_id' => $order?->id,
                'online_sale_order_item_id' => $item?->id,
                'submission_path' => $path,
                'rating' => $rating,
                'body' => $body,
                'proof_note' => $proofNote !== '' ? $proofNote : null,
                'status' => ProductReview::STATUS_PENDING,
                'moderated_by' => null,
                'moderated_at' => null,
            ];

            if ($existing !== null) {
                $existing->update($payload);
                $review = $existing->fresh();
            } else {
                $review = ProductReview::query()->create($payload);
            }

            if ($galleryPhotos !== []) {
                $this->replacePhotosByKind($review, $galleryPhotos, ProductReviewPhoto::KIND_GALLERY);
            }

            if ($proofFiles !== []) {
                $this->replacePhotosByKind($review, $proofFiles, ProductReviewPhoto::KIND_PROOF);
            }

            return $review->fresh(['photos']);
        });
    }

    /**
     * @param  list<UploadedFile>  $photos
     */
    public function replacePhotos(ProductReview $review, array $photos): void
    {
        $this->replacePhotosByKind($review, $photos, ProductReviewPhoto::KIND_GALLERY);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function replacePhotosByKind(ProductReview $review, array $files, string $kind): void
    {
        if (! in_array($kind, ProductReviewPhoto::KINDS, true)) {
            $kind = ProductReviewPhoto::KIND_GALLERY;
        }

        $existing = $review->photos()->where('kind', $kind)->get();

        foreach ($existing as $photo) {
            $this->deletePhotoFile($photo);
            $photo->delete();
        }

        $sort = 0;

        foreach ($files as $upload) {
            if (! $upload instanceof UploadedFile) {
                continue;
            }

            $path = $upload->store('product-reviews', 'public');

            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages([
                    $kind === ProductReviewPhoto::KIND_PROOF ? 'proofs' : 'photos' => [
                        __('agricart.storefront.reviews.errors.'.($kind === ProductReviewPhoto::KIND_PROOF ? 'proofs' : 'photos')),
                    ],
                ]);
            }

            ProductReviewPhoto::query()->create([
                'product_review_id' => $review->id,
                'path' => $path,
                'kind' => $kind,
                'sort_order' => $sort++,
            ]);
        }
    }

    public function deletePhoto(ProductReviewPhoto $photo): void
    {
        $this->deletePhotoFile($photo);
        $photo->delete();
    }

    public function setStatus(ProductReview $review, string $status, User $moderator): ProductReview
    {
        if (! in_array($status, ProductReview::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => [__('agricart.store.admin.reviews.errors.invalid_status')],
            ]);
        }

        $review->update([
            'status' => $status,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        return $review->fresh();
    }

    public function setAdminReply(ProductReview $review, ?string $reply, User $moderator): ProductReview
    {
        $reply = $reply !== null ? trim($reply) : '';

        if ($reply === '') {
            $review->update([
                'admin_reply' => null,
                'admin_replied_by' => null,
                'admin_replied_at' => null,
            ]);

            return $review->fresh();
        }

        if (mb_strlen($reply) > 2000) {
            throw ValidationException::withMessages([
                'admin_reply' => [__('agricart.storefront.reviews.errors.body')],
            ]);
        }

        $review->update([
            'admin_reply' => $reply,
            'admin_replied_by' => $moderator->id,
            'admin_replied_at' => now(),
        ]);

        return $review->fresh();
    }

    public function report(
        ProductReview $review,
        Customer $customer,
        string $reason,
        ?string $note = null,
    ): ProductReviewReport {
        if ($review->status !== ProductReview::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'review' => [__('agricart.storefront.reviews.errors.not_eligible')],
            ]);
        }

        if (! in_array($reason, ProductReviewReport::REASONS, true)) {
            throw ValidationException::withMessages([
                'reason' => [__('agricart.storefront.reviews.errors.report_duplicate')],
            ]);
        }

        $existing = ProductReviewReport::query()
            ->where('product_review_id', $review->id)
            ->where('customer_id', $customer->id)
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'review' => [__('agricart.storefront.reviews.errors.report_duplicate')],
            ]);
        }

        $note = $note !== null ? trim($note) : '';

        return ProductReviewReport::query()->create([
            'product_review_id' => $review->id,
            'customer_id' => $customer->id,
            'reason' => $reason,
            'note' => $note !== '' ? mb_substr($note, 0, 500) : null,
            'status' => ProductReviewReport::STATUS_OPEN,
        ]);
    }

    public function resolveReport(ProductReviewReport $report, string $status = ProductReviewReport::STATUS_RESOLVED): void
    {
        if (! in_array($status, [ProductReviewReport::STATUS_RESOLVED, ProductReviewReport::STATUS_DISMISSED], true)) {
            return;
        }

        $report->update(['status' => $status]);
    }

    public function delete(ProductReview $review): void
    {
        foreach ($review->photos as $photo) {
            $this->deletePhotoFile($photo);
        }

        $review->delete();
    }

    private function deletePhotoFile(ProductReviewPhoto $photo): void
    {
        if (filled($photo->path)) {
            Storage::disk('public')->delete($photo->path);
        }
    }
}
