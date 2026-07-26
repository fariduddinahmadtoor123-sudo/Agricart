@php
    $summary = $reviewSummary ?? ['average' => null, 'count' => 0];
    $reviews = $publicReviews ?? collect();
    $canWriteReview = $canWriteReview ?? false;
@endphp

<div class="ag-store-pdp__customer-reviews">
    <div class="ag-store-pdp__customer-reviews-head">
        <div>
            <p class="ag-store-pdp__customer-reviews-eyebrow">{{ __('agricart.storefront.reviews.eyebrow') }}</p>
            @if ($summary['count'] > 0 && $summary['average'] !== null)
                <p class="ag-store-pdp__customer-reviews-score">
                    <span class="ag-store-pdp__customer-reviews-average">{{ number_format($summary['average'], 1) }}</span>
                    <span class="ag-store-pdp__customer-reviews-of">/ 5</span>
                    <span class="ag-store-pdp__customer-reviews-count">
                        {{ __('agricart.storefront.reviews.count', ['count' => $summary['count']]) }}
                    </span>
                </p>
                <p class="ag-store-pdp__customer-reviews-stars" aria-hidden="true">
                    @for ($i = 1; $i <= 5; $i++)
                        <span @class(['is-on' => $i <= (int) round($summary['average'])])>★</span>
                    @endfor
                </p>
            @else
                <p class="ag-store-pdp__customer-reviews-empty-lead">{{ __('agricart.storefront.reviews.empty') }}</p>
            @endif
        </div>
        @if ($canWriteReview)
            <a href="{{ route('store.customer.reviews.create', $product->slug) }}" class="ag-store-pdp__customer-reviews-write">
                {{ __('agricart.storefront.reviews.write_short') }}
            </a>
        @elseif (! auth('customer')->check())
            <a href="{{ route('store.customer.login') }}" class="ag-store-pdp__customer-reviews-write">
                {{ __('agricart.storefront.reviews.login_to_review') }}
            </a>
        @endif
    </div>

    @auth('customer')
        @if (! $canWriteReview)
            <p class="ag-store-pdp__customer-reviews-hint">{{ __('agricart.storefront.reviews.already_published_hint') }}</p>
        @else
            <p class="ag-store-pdp__customer-reviews-hint">{{ __('agricart.storefront.reviews.eligibility_hint') }}</p>
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
                            {{ $review->customer?->displayName() ?: __('agricart.storefront.reviews.anonymous') }}
                        </span>
                        @if ($review->isVerifiedPurchase())
                            <span class="ag-store-pdp__customer-review-verified">{{ __('agricart.storefront.reviews.verified') }}</span>
                        @elseif ($review->isProofSubmission())
                            <span class="ag-store-pdp__customer-review-verified">{{ __('agricart.storefront.reviews.reviewed_with_proof') }}</span>
                        @endif
                        <span class="ag-store-pdp__customer-review-rating" aria-label="{{ __('agricart.storefront.reviews.rating_aria', ['rating' => $review->rating]) }}">
                            @for ($i = 1; $i <= 5; $i++)
                                <span @class(['is-on' => $i <= $review->rating])>★</span>
                            @endfor
                        </span>
                    </div>
                    <p class="ag-store-pdp__customer-review-body">{{ $review->body }}</p>
                    @if ($review->photos->isNotEmpty())
                        <ul class="ag-store-pdp__customer-review-photos">
                            @foreach ($review->photos as $photo)
                                <li>
                                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                        <img src="{{ $photo->url() }}" alt="" width="96" height="96" loading="lazy" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (filled($review->admin_reply))
                        <div class="ag-store-pdp__customer-review-reply">
                            <p class="ag-store-pdp__customer-review-reply-label">{{ __('agricart.storefront.reviews.admin_reply') }}</p>
                            <p class="ag-store-pdp__customer-review-reply-body">{{ $review->admin_reply }}</p>
                        </div>
                    @endif
                    <div class="ag-store-pdp__customer-review-footer">
                        <p class="ag-store-pdp__customer-review-date">{{ $review->created_at?->format('Y-m-d') }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
