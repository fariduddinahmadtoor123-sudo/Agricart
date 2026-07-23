@php
    use App\Support\Catalog\BrandAgricartReviewCriteria;

    $rating = $product->agricart_rating;
    $reason = $presenter->localizedAgricartProductRatingReason($product);
    $revision = $presenter->localizedAgricartProductRatingRevision($product);
    $notice = $presenter->brandReviewNoticeText();
    $criteria = BrandAgricartReviewCriteria::normalizeDetailed($product->agricart_rating_criteria);
    $locale = app()->getLocale();
@endphp

<div class="ag-store-pdp__brand-review">
    <div class="ag-store-pdp__brand-review-head">
        <div class="ag-store-pdp__brand-about-meta">
            <p class="ag-store-pdp__brand-review-eyebrow">{{ __('agricart.storefront.product.product_review.eyebrow') }}</p>
        </div>
    </div>

    @if ($rating !== null)
        <div class="ag-store-pdp__brand-review-score" aria-label="{{ __('agricart.storefront.product.product_review.score_aria', ['score' => $rating]) }}">
            <span class="ag-store-pdp__brand-review-score-value">{{ $rating }}</span>
            <span class="ag-store-pdp__brand-review-score-of">/ {{ BrandAgricartReviewCriteria::scoreMax() }}</span>
        </div>
    @endif

    <p class="ag-store-pdp__brand-review-fairness">{{ __('agricart.storefront.product.product_review.fairness') }}</p>

    @if (BrandAgricartReviewCriteria::isComplete($criteria))
        <ul class="ag-store-pdp__brand-review-criteria">
            @foreach (BrandAgricartReviewCriteria::keys() as $criterionKey)
                @php
                    $detail = $criteria[$criterionKey];
                    $band = BrandAgricartReviewCriteria::bandForRating($detail['rating']) ?? BrandAgricartReviewCriteria::BAND_NEUTRAL;
                    $itemReason = $locale === 'ur' && filled($detail['reason_ur'])
                        ? $detail['reason_ur']
                        : ($detail['reason_en'] ?: $detail['reason_ur']);
                @endphp
                <li @class([
                    'ag-store-pdp__brand-review-criterion',
                    'is-positive' => $band === BrandAgricartReviewCriteria::BAND_POSITIVE,
                    'is-neutral' => $band === BrandAgricartReviewCriteria::BAND_NEUTRAL,
                    'is-needs-improvement' => $band === BrandAgricartReviewCriteria::BAND_NEEDS_IMPROVEMENT,
                ])>
                    <span class="ag-store-pdp__brand-review-mark" aria-hidden="true">{{ $detail['rating'] }}</span>
                    <span class="ag-store-pdp__brand-review-criterion-body">
                        <span class="ag-store-pdp__brand-review-criterion-label">
                            {{ __('agricart.brands.review_criteria.'.$criterionKey.'.label') }}
                            @if ($locale === 'ur')
                                <span class="ag-store-pdp__brand-review-criterion-label-ur" dir="rtl">{{ __('agricart.brands.review_criteria.'.$criterionKey.'.label_ur') }}</span>
                            @endif
                        </span>
                        <span class="ag-store-pdp__brand-review-criterion-check">{{ __('agricart.brands.review_criteria.'.$criterionKey.'.check') }}</span>
                        <span class="ag-store-pdp__brand-review-criterion-result">
                            {{ __('agricart.storefront.product.brand_review.'.$band) }}
                            ({{ $detail['rating'] }}/{{ BrandAgricartReviewCriteria::MAX_RATING }})
                        </span>
                        @if (filled($itemReason))
                            <span class="ag-store-pdp__brand-review-criterion-reason" @if ($locale === 'ur') dir="rtl" @endif>
                                {{ $itemReason }}
                            </span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    @if (filled($revision))
        <div class="ag-store-pdp__brand-review-revision">
            <p class="ag-store-pdp__brand-review-notes-title">{{ __('agricart.storefront.product.product_review.revision') }}</p>
            <div class="ag-store-pdp__prose ag-store-pdp__brand-review-body">{!! nl2br(e($revision)) !!}</div>
        </div>
    @endif

    @if (filled($reason))
        <div class="ag-store-pdp__brand-review-notes">
            <p class="ag-store-pdp__brand-review-notes-title">{{ __('agricart.storefront.product.product_review.notes') }}</p>
            <div class="ag-store-pdp__prose ag-store-pdp__brand-review-body">{!! nl2br(e($reason)) !!}</div>
        </div>
    @endif

    @if (filled($notice))
        <aside class="ag-store-pdp__brand-review-notice" aria-label="{{ __('agricart.storefront.product.brand_review.notice_title') }}">
            <p class="ag-store-pdp__brand-review-notice-title">{{ __('agricart.storefront.product.brand_review.notice_title') }}</p>
            <div class="ag-store-pdp__prose ag-store-pdp__brand-review-notice-body">{!! nl2br(e($notice)) !!}</div>
        </aside>
    @endif
</div>
