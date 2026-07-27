<?php

namespace App\Services\Store;

use App\Models\Brand;
use App\Models\BrandCustomerReview;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BrandCustomerReviewService
{
    public function existingReview(Customer $customer, Brand $brand): ?BrandCustomerReview
    {
        return BrandCustomerReview::query()
            ->where('customer_id', $customer->id)
            ->where('brand_id', $brand->id)
            ->first();
    }

    public function canSubmit(Customer $customer, Brand $brand): bool
    {
        $existing = $this->existingReview($customer, $brand);

        if ($existing === null) {
            return true;
        }

        return in_array($existing->status, [
            BrandCustomerReview::STATUS_PENDING,
            BrandCustomerReview::STATUS_REJECTED,
            BrandCustomerReview::STATUS_HIDDEN,
        ], true);
    }

    /**
     * @return array{average: float|null, count: int}
     */
    public function publicSummary(Brand $brand): array
    {
        $count = (int) BrandCustomerReview::query()
            ->where('brand_id', $brand->id)
            ->where('status', BrandCustomerReview::STATUS_APPROVED)
            ->count();

        if ($count === 0) {
            return ['average' => null, 'count' => 0];
        }

        $average = (float) BrandCustomerReview::query()
            ->where('brand_id', $brand->id)
            ->where('status', BrandCustomerReview::STATUS_APPROVED)
            ->avg('rating');

        return [
            'average' => round($average, 1),
            'count' => $count,
        ];
    }

    /**
     * @return Collection<int, BrandCustomerReview>
     */
    public function publicReviews(Brand $brand, int $limit = 50): Collection
    {
        return BrandCustomerReview::query()
            ->where('brand_id', $brand->id)
            ->where('status', BrandCustomerReview::STATUS_APPROVED)
            ->with('customer')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array{rating: mixed, body: mixed}  $data
     */
    public function submit(Customer $customer, Brand $brand, array $data): BrandCustomerReview
    {
        if (! $this->canSubmit($customer, $brand)) {
            throw ValidationException::withMessages([
                'body' => [__('agricart.storefront.brand_reviews.errors.already_approved')],
            ]);
        }

        $validated = Validator::make($data, [
            'rating' => ['required', 'integer', 'min:'.BrandCustomerReview::MIN_RATING, 'max:'.BrandCustomerReview::MAX_RATING],
            'body' => ['required', 'string', 'min:10', 'max:'.BrandCustomerReview::MAX_BODY_LENGTH],
        ], [
            'rating.required' => __('agricart.storefront.brand_reviews.errors.rating'),
            'body.required' => __('agricart.storefront.brand_reviews.errors.body'),
            'body.min' => __('agricart.storefront.brand_reviews.errors.body'),
            'body.max' => __('agricart.storefront.brand_reviews.errors.body'),
        ])->validate();

        $existing = $this->existingReview($customer, $brand);

        if ($existing) {
            $existing->fill([
                'rating' => (int) $validated['rating'],
                'body' => trim((string) $validated['body']),
                'status' => BrandCustomerReview::STATUS_PENDING,
                'moderated_by' => null,
                'moderated_at' => null,
            ])->save();

            return $existing->refresh();
        }

        return BrandCustomerReview::query()->create([
            'brand_id' => $brand->id,
            'customer_id' => $customer->id,
            'rating' => (int) $validated['rating'],
            'body' => trim((string) $validated['body']),
            'status' => BrandCustomerReview::STATUS_PENDING,
        ]);
    }

    public function setStatus(BrandCustomerReview $review, string $status, User $actor): BrandCustomerReview
    {
        if (! in_array($status, BrandCustomerReview::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => [__('agricart.store.admin.brand_reviews.errors.invalid_status')],
            ]);
        }

        $review->update([
            'status' => $status,
            'moderated_by' => $actor->id,
            'moderated_at' => now(),
        ]);

        return $review->refresh();
    }

    public function setAdminReply(BrandCustomerReview $review, ?string $reply, User $actor): BrandCustomerReview
    {
        $text = filled($reply) ? trim($reply) : null;

        if ($text !== null && mb_strlen($text) > 2000) {
            throw ValidationException::withMessages([
                'admin_reply' => [__('agricart.store.admin.brand_reviews.errors.reply')],
            ]);
        }

        $review->update([
            'admin_reply' => $text,
            'admin_replied_by' => $text !== null ? $actor->id : null,
            'admin_replied_at' => $text !== null ? now() : null,
        ]);

        return $review->refresh();
    }

    public function delete(BrandCustomerReview $review): void
    {
        $review->delete();
    }
}
