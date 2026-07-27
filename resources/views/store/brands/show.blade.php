@php
    use App\Support\Catalog\BrandAgricartReviewCriteria;

    /** @var \App\Models\Brand $brand */
    /** @var \App\Services\Store\StoreBrandService $brandService */
    $brandName = $brandService->localizedName($brand);
    $brandLogoUrl = $brandService->imageUrl($brand);
    $brandDescription = $brandService->localizedDescription($brand);
    $reason = $brandService->localizedReason($brand);
    $revision = $brandService->localizedRevision($brand);
    $notice = $brandService->reviewNoticeText();
    $criteria = BrandAgricartReviewCriteria::normalizeDetailed($brand->agricart_rating_criteria);
    $locale = app()->getLocale();
    $websiteUrl = filled($brand->website_url) ? $brand->website_url : null;
@endphp

@extends('store.layouts.app')

@section('title', $brandName . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', \Illuminate\Support\Str::limit(trim(strip_tags($brandDescription ?? __('agricart.storefront.brands.page_lead'))), 160))
@section('canonical', route('store.brand', $brand->slug))
@section('robots', 'index,follow,max-image-preview:large')
@push('structured-data')
    <script type="application/ld+json">{!! app(\App\Services\Store\StorefrontSchemaService::class)->toJson($schemaDocument) !!}</script>
@endpush

@section('content')
    <nav class="ag-store-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('store.home') }}">{{ __('agricart.storefront.category.home') }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('store.brands') }}">{{ __('agricart.storefront.brands.nav') }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ $brandName }}</span>
    </nav>

    <article class="ag-store-brand">
        @if (session('customer_auth_status'))
            <div class="ag-store-alert ag-store-alert--success" role="status">{{ session('customer_auth_status') }}</div>
        @endif

        <header class="ag-store-brand__hero">
            <div class="ag-store-brand__logo">
                @if ($brandLogoUrl)
                    <img src="{{ $brandLogoUrl }}" alt="" width="120" height="120" />
                @else
                    <span aria-hidden="true">{{ strtoupper(mb_substr($brand->name_en, 0, 1)) }}</span>
                @endif
            </div>
            <div class="ag-store-brand__intro">
                <p class="ag-store-brand__eyebrow">{{ __('agricart.storefront.brands.detail_eyebrow') }}</p>
                <h1 class="ag-store-brand__title">{{ $brand->name_en }}</h1>
                @if (filled($brand->name_ur))
                    <p class="ag-store-brand__title-ur" dir="rtl">{{ $brand->name_ur }}</p>
                @endif
                @include('store.components.brand-ratings-pair', [
                    'agricartSummary' => $ratingSummary,
                    'customerSummary' => $customerReviewSummary,
                    'variant' => 'full',
                ])
                @if ($websiteUrl)
                    <a href="{{ $websiteUrl }}" class="ag-store-brand__website" target="_blank" rel="noopener noreferrer">
                        {{ __('agricart.storefront.product.brand_website') }}
                    </a>
                @endif
            </div>
        </header>

        @if (filled($brandDescription))
            <section class="ag-store-brand__section" aria-labelledby="brand-about-heading">
                <h2 id="brand-about-heading" class="ag-store-brand__heading">{{ __('agricart.storefront.brands.about_heading') }}</h2>
                <div class="ag-store-brand__prose">{!! nl2br(e($brandDescription)) !!}</div>
            </section>
        @endif

        @if ($hasReview)
            <section class="ag-store-brand__section ag-store-brand__review" aria-labelledby="brand-review-heading">
                <h2 id="brand-review-heading" class="ag-store-brand__heading">{{ __('agricart.storefront.product.tabs.agricart_brand_review') }}</h2>

                <div class="ag-store-pdp__brand-review">
                    <div class="ag-store-pdp__brand-review-head">
                        @if ($brandLogoUrl)
                            <img src="{{ $brandLogoUrl }}" alt="" class="ag-store-pdp__brand-about-logo" loading="lazy" width="56" height="56" />
                        @endif
                        <div class="ag-store-pdp__brand-about-meta">
                            <p class="ag-store-pdp__brand-about-name">{{ $brandName }}</p>
                            <p class="ag-store-pdp__brand-review-eyebrow">{{ __('agricart.storefront.product.brand_review.eyebrow') }}</p>
                        </div>
                    </div>

                    @if ($brand->agricart_rating !== null)
                        <div class="ag-store-pdp__brand-review-score" aria-label="{{ __('agricart.storefront.product.brand_review.score_aria', ['score' => $brand->agricart_rating]) }}">
                            <span class="ag-store-pdp__brand-review-score-value">{{ $brand->agricart_rating }}</span>
                            <span class="ag-store-pdp__brand-review-score-of">/ {{ BrandAgricartReviewCriteria::scoreMax() }}</span>
                        </div>
                    @endif

                    <p class="ag-store-pdp__brand-review-fairness">{{ __('agricart.storefront.product.brand_review.fairness') }}</p>

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
                            <p class="ag-store-pdp__brand-review-notes-title">{{ __('agricart.storefront.product.brand_review.revision') }}</p>
                            <div class="ag-store-pdp__prose ag-store-pdp__brand-review-body">{!! nl2br(e($revision)) !!}</div>
                        </div>
                    @endif

                    @if (filled($reason))
                        <div class="ag-store-pdp__brand-review-notes">
                            <p class="ag-store-pdp__brand-review-notes-title">{{ __('agricart.storefront.product.brand_review.notes') }}</p>
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
            </section>
        @endif

        @include('store.components.brand-customer-reviews')

        <div class="ag-store-brand__back">
            <a href="{{ route('store.brands') }}" class="ag-store-btn ag-store-btn--secondary">
                {{ __('agricart.storefront.brands.back_to_all') }}
            </a>
        </div>
    </article>
@endsection
