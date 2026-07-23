@php
    use App\Support\Catalog\BrandAgricartReviewCriteria;

    $brandName = $presenter->localizedBrandName($product);
    $brandLogoUrl = $presenter->brandImageUrl($product);
    $rating = $product->brand?->agricart_rating;
    $reason = $presenter->localizedAgricartRatingReason($product);
    $criteria = BrandAgricartReviewCriteria::normalize($product->brand?->agricart_rating_criteria);
    $locale = app()->getLocale();
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

    <p class="ag-store-pdp__brand-review-fairness">{{ __('agricart.storefront.product.brand_review.fairness') }}</p>

    @if (BrandAgricartReviewCriteria::isComplete($criteria))
        <ul class="ag-store-pdp__brand-review-criteria">
            @foreach (BrandAgricartReviewCriteria::keys() as $criterionKey)
                @php($passed = $criteria[$criterionKey] === true)
                <li @class(['ag-store-pdp__brand-review-criterion', 'is-pass' => $passed, 'is-fail' => ! $passed])>
                    <span class="ag-store-pdp__brand-review-mark" aria-hidden="true">{{ $passed ? '✓' : '✗' }}</span>
                    <span class="ag-store-pdp__brand-review-criterion-body">
                        <span class="ag-store-pdp__brand-review-criterion-label">
                            {{ __('agricart.brands.review_criteria.'.$criterionKey.'.label') }}
                            @if ($locale === 'ur')
                                <span class="ag-store-pdp__brand-review-criterion-label-ur" dir="rtl">{{ __('agricart.brands.review_criteria.'.$criterionKey.'.label_ur') }}</span>
                            @endif
                        </span>
                        <span class="ag-store-pdp__brand-review-criterion-check">{{ __('agricart.brands.review_criteria.'.$criterionKey.'.check') }}</span>
                        <span class="ag-store-pdp__brand-review-criterion-result">
                            {{ $passed
                                ? __('agricart.storefront.product.brand_review.pass')
                                : __('agricart.storefront.product.brand_review.fail') }}
                        </span>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    @if (filled($reason))
        <div class="ag-store-pdp__brand-review-notes">
            <p class="ag-store-pdp__brand-review-notes-title">{{ __('agricart.storefront.product.brand_review.notes') }}</p>
            <div class="ag-store-pdp__prose ag-store-pdp__brand-review-body">{!! nl2br(e($reason)) !!}</div>
        </div>
    @endif
</div>
