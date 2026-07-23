@php
    $brandName = $presenter->localizedBrandName($product);
    $brandLogoUrl = $presenter->brandImageUrl($product);
    $rating = $product->brand?->agricart_rating;
    $reason = $presenter->localizedAgricartRatingReason($product);
@endphp

<div class="ag-store-pdp__brand-review">
    <div class="ag-store-pdp__brand-review-head">
        @if ($brandLogoUrl)
            <img
                src="{{ $brandLogoUrl }}"
                alt=""
                class="ag-store-pdp__brand-about-logo"
                loading="lazy"
                width="56"
                height="56"
            />
        @endif
        <div class="ag-store-pdp__brand-about-meta">
            @if (filled($brandName))
                <p class="ag-store-pdp__brand-about-name">{{ $brandName }}</p>
            @endif
            <p class="ag-store-pdp__brand-review-eyebrow">{{ __('agricart.storefront.product.brand_review.eyebrow') }}</p>
        </div>
    </div>

    @if ($rating !== null)
        <div class="ag-store-pdp__brand-review-score" aria-label="{{ __('agricart.storefront.product.brand_review.score_aria', ['score' => $rating]) }}">
            <span class="ag-store-pdp__brand-review-score-value">{{ $rating }}</span>
            <span class="ag-store-pdp__brand-review-score-of">{{ __('agricart.storefront.product.brand_review.out_of') }}</span>
        </div>
    @endif

    @if (filled($reason))
        <div class="ag-store-pdp__prose ag-store-pdp__brand-review-body">{!! nl2br(e($reason)) !!}</div>
    @endif
</div>
