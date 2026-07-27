@php
    $summary = $customerReviewSummary ?? ['average' => null, 'count' => 0];
    $reviews = $customerReviews ?? collect();
    $canWriteReview = $canWriteBrandReview ?? false;
@endphp

<section class="ag-store-brand__section ag-store-brand__customer-reviews" aria-labelledby="brand-customer-reviews-heading">
    <div class="ag-store-pdp__customer-reviews">
        <div class="ag-store-pdp__customer-reviews-head">
            <div>
                <p class="ag-store-pdp__customer-reviews-eyebrow" id="brand-customer-reviews-heading">
                    {{ __('agricart.storefront.brand_reviews.eyebrow') }}
                </p>
                @if ($summary['count'] > 0 && $summary['average'] !== null)
                    <p class="ag-store-pdp__customer-reviews-score">
                        <span class="ag-store-pdp__customer-reviews-average">{{ number_format($summary['average'], 1) }}</span>
                        <span class="ag-store-pdp__customer-reviews-of">/ 5</span>
                        <span class="ag-store-pdp__customer-reviews-count">
                            {{ __('agricart.storefront.brand_reviews.count', ['count' => $summary['count']]) }}
                        </span>
                    </p>
                    <p class="ag-store-pdp__customer-reviews-stars" aria-hidden="true">
                        @for ($i = 1; $i <= 5; $i++)
                            <span @class(['is-on' => $i <= (int) round($summary['average'])])>★</span>
                        @endfor
                    </p>
                @else
                    <p class="ag-store-pdp__customer-reviews-empty-lead">{{ __('agricart.storefront.brand_reviews.empty') }}</p>
                @endif
            </div>
            @if ($canWriteReview)
                <a href="{{ route('store.customer.brand-reviews.create', $brand->slug) }}" class="ag-store-pdp__customer-reviews-write">
                    {{ __('agricart.storefront.brand_reviews.write_short') }}
                </a>
            @elseif (! auth('customer')->check())
                <a
                    href="{{ route('store.customer.login', ['redirect' => route('store.customer.brand-reviews.create', $brand->slug, absolute: false)]) }}"
                    class="ag-store-pdp__customer-reviews-write"
                >
                    {{ __('agricart.storefront.brand_reviews.login_to_review') }}
                </a>
            @endif
        </div>

        @auth('customer')
            @if (! $canWriteReview)
                <p class="ag-store-pdp__customer-reviews-hint">{{ __('agricart.storefront.brand_reviews.already_published_hint') }}</p>
            @else
                <p class="ag-store-pdp__customer-reviews-hint">{{ __('agricart.storefront.brand_reviews.eligibility_hint') }}</p>
            @endif
        @endauth

        @if ($reviews->isNotEmpty())
            <ul class="ag-store-pdp__customer-review-list">
                @foreach ($reviews as $review)
                    <li class="ag-store-pdp__customer-review-item">
                        <div class="ag-store-pdp__customer-review-meta">
                            <span class="ag-store-pdp__customer-review-author">
                                @if ($review->customer?->profilePhotoUrl())
                                    <img src="{{ $review->customer->profilePhotoUrl() }}" alt="" class="ag-store-pdp__customer-review-avatar" width="28" height="28" loading="lazy" />
                                @endif
                                {{ $review->customer?->displayName() ?: __('agricart.storefront.brand_reviews.anonymous') }}
                            </span>
                            <span class="ag-store-pdp__customer-review-rating" aria-label="{{ __('agricart.storefront.brand_reviews.rating_aria', ['rating' => $review->rating]) }}">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span @class(['is-on' => $i <= $review->rating])>★</span>
                                @endfor
                            </span>
                        </div>
                        <p class="ag-store-pdp__customer-review-body">{{ $review->body }}</p>
                        @if (filled($review->admin_reply))
                            <div class="ag-store-pdp__customer-review-reply">
                                <p class="ag-store-pdp__customer-review-reply-label">{{ __('agricart.storefront.brand_reviews.admin_reply') }}</p>
                                <p class="ag-store-pdp__customer-review-reply-body">{{ $review->admin_reply }}</p>
                            </div>
                        @endif
                        <p class="ag-store-pdp__customer-review-date">{{ $review->created_at?->format('Y-m-d') }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
