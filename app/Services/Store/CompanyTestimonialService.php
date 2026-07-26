<?php

namespace App\Services\Store;

use App\Models\CompanyTestimonial;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompanyTestimonialService
{
    /**
     * @return array{average: ?float, count: int}
     */
    public function publicSummary(): array
    {
        $query = CompanyTestimonial::query()->where('status', CompanyTestimonial::STATUS_APPROVED);
        $count = (int) $query->count();

        if ($count === 0) {
            return ['average' => null, 'count' => 0];
        }

        $average = (float) CompanyTestimonial::query()
            ->where('status', CompanyTestimonial::STATUS_APPROVED)
            ->avg('rating');

        return [
            'average' => round($average, 1),
            'count' => $count,
        ];
    }

    /**
     * @return Collection<int, CompanyTestimonial>
     */
    public function publicReviews(int $limit = 50): Collection
    {
        return CompanyTestimonial::query()
            ->where('status', CompanyTestimonial::STATUS_APPROVED)
            ->with(['customer:id,profile_photo_path,first_name,last_name,display_name,name_en,name_ur'])
            ->orderByDesc('is_featured')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, CompanyTestimonial>
     */
    public function featuredForHome(int $limit = 3): Collection
    {
        $withCustomer = ['customer:id,profile_photo_path,first_name,last_name,display_name,name_en,name_ur'];

        $featured = CompanyTestimonial::query()
            ->where('status', CompanyTestimonial::STATUS_APPROVED)
            ->where('is_featured', true)
            ->with($withCustomer)
            ->latest('id')
            ->limit($limit)
            ->get();

        if ($featured->count() >= $limit) {
            return $featured;
        }

        $remaining = $limit - $featured->count();
        $fallback = CompanyTestimonial::query()
            ->where('status', CompanyTestimonial::STATUS_APPROVED)
            ->where('is_featured', false)
            ->whereNotIn('id', $featured->pluck('id'))
            ->with($withCustomer)
            ->latest('id')
            ->limit($remaining)
            ->get();

        return $featured->concat($fallback)->values();
    }

    public function existingForCustomer(Customer $customer): ?CompanyTestimonial
    {
        return CompanyTestimonial::query()
            ->where('customer_id', $customer->id)
            ->first();
    }

    public function submit(Customer $customer, int $rating, string $body, ?string $title = null): CompanyTestimonial
    {
        $rating = max(CompanyTestimonial::MIN_RATING, min(CompanyTestimonial::MAX_RATING, $rating));
        $body = trim($body);
        $title = $title !== null ? trim($title) : '';

        if ($body === '' || mb_strlen($body) < 10 || mb_strlen($body) > CompanyTestimonial::MAX_BODY_LENGTH) {
            throw ValidationException::withMessages([
                'body' => [__('agricart.storefront.testimonials.errors.body')],
            ]);
        }

        if ($title !== '' && mb_strlen($title) > CompanyTestimonial::MAX_TITLE_LENGTH) {
            throw ValidationException::withMessages([
                'title' => [__('agricart.storefront.testimonials.errors.title')],
            ]);
        }

        $displayName = trim($customer->displayName());
        if ($displayName === '') {
            $displayName = trim($customer->fullName());
        }
        if ($displayName === '') {
            $displayName = __('agricart.storefront.testimonials.anonymous');
        }

        $existing = $this->existingForCustomer($customer);
        $keepPublished = $existing !== null && $existing->status === CompanyTestimonial::STATUS_APPROVED;

        $payload = [
            'customer_id' => $customer->id,
            'display_name' => mb_substr($displayName, 0, 120),
            'title' => $title !== '' ? $title : null,
            'rating' => $rating,
            'body' => $body,
            'status' => $keepPublished
                ? CompanyTestimonial::STATUS_APPROVED
                : CompanyTestimonial::STATUS_PENDING,
            'is_featured' => $keepPublished ? (bool) $existing->is_featured : false,
            'source' => CompanyTestimonial::SOURCE_CUSTOMER,
            'moderated_by' => $keepPublished ? $existing->moderated_by : null,
            'moderated_at' => $keepPublished ? $existing->moderated_at : null,
        ];

        if ($existing !== null) {
            $existing->update($payload);

            return $existing->fresh();
        }

        return CompanyTestimonial::query()->create($payload);
    }

    public function createFromAdmin(
        User $admin,
        string $displayName,
        int $rating,
        string $body,
        ?string $title = null,
        bool $featured = false,
        bool $approve = true,
    ): CompanyTestimonial {
        $displayName = trim($displayName);
        $body = trim($body);
        $title = $title !== null ? trim($title) : '';
        $rating = max(CompanyTestimonial::MIN_RATING, min(CompanyTestimonial::MAX_RATING, $rating));

        if ($displayName === '' || mb_strlen($displayName) > 120) {
            throw ValidationException::withMessages([
                'display_name' => [__('agricart.store.admin.testimonials.errors.display_name')],
            ]);
        }

        if ($body === '' || mb_strlen($body) < 10 || mb_strlen($body) > CompanyTestimonial::MAX_BODY_LENGTH) {
            throw ValidationException::withMessages([
                'body' => [__('agricart.store.admin.testimonials.errors.body')],
            ]);
        }

        return CompanyTestimonial::query()->create([
            'customer_id' => null,
            'display_name' => $displayName,
            'title' => $title !== '' ? $title : null,
            'rating' => $rating,
            'body' => $body,
            'status' => $approve ? CompanyTestimonial::STATUS_APPROVED : CompanyTestimonial::STATUS_PENDING,
            'is_featured' => $featured,
            'source' => CompanyTestimonial::SOURCE_ADMIN,
            'moderated_by' => $approve ? $admin->id : null,
            'moderated_at' => $approve ? now() : null,
        ]);
    }

    public function setStatus(CompanyTestimonial $testimonial, string $status, User $moderator): CompanyTestimonial
    {
        if (! in_array($status, CompanyTestimonial::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => [__('agricart.store.admin.testimonials.errors.invalid_status')],
            ]);
        }

        $testimonial->update([
            'status' => $status,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
            'is_featured' => $status === CompanyTestimonial::STATUS_APPROVED
                ? $testimonial->is_featured
                : false,
        ]);

        return $testimonial->fresh();
    }

    public function setFeatured(CompanyTestimonial $testimonial, bool $featured): CompanyTestimonial
    {
        if ($featured && ! $testimonial->isApproved()) {
            throw ValidationException::withMessages([
                'is_featured' => [__('agricart.store.admin.testimonials.errors.feature_requires_approved')],
            ]);
        }

        $testimonial->update(['is_featured' => $featured]);

        return $testimonial->fresh();
    }

    public function setAdminReply(CompanyTestimonial $testimonial, ?string $reply, User $moderator): CompanyTestimonial
    {
        $reply = $reply !== null ? trim($reply) : '';

        if ($reply === '') {
            $testimonial->update([
                'admin_reply' => null,
                'admin_replied_by' => null,
                'admin_replied_at' => null,
            ]);

            return $testimonial->fresh();
        }

        if (mb_strlen($reply) > 2000) {
            throw ValidationException::withMessages([
                'admin_reply' => [__('agricart.store.admin.testimonials.errors.reply')],
            ]);
        }

        $testimonial->update([
            'admin_reply' => $reply,
            'admin_replied_by' => $moderator->id,
            'admin_replied_at' => now(),
        ]);

        return $testimonial->fresh();
    }

    public function delete(CompanyTestimonial $testimonial): void
    {
        $testimonial->delete();
    }
}
