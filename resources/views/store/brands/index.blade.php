@extends('store.layouts.app')

@section('title', __('agricart.storefront.brands.page_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', __('agricart.storefront.brands.page_lead'))
@section('canonical', route('store.brands'))
@section('robots', filled($searchQuery ?? '') ? 'noindex,follow' : 'index,follow,max-image-preview:large')
@push('structured-data')
    <script type="application/ld+json">{!! app(\App\Services\Store\StorefrontSchemaService::class)->toJson($schemaDocument) !!}</script>
@endpush

@section('content')
    <section class="ag-store-brands">
        <header class="ag-store-brands__header">
            <h1 class="ag-store-visually-hidden">{{ __('agricart.storefront.brands.heading') }}</h1>
            <p class="ag-store-brands__lead">{{ __('agricart.storefront.brands.page_lead') }}</p>
        </header>

        <form
            class="ag-store-brands__search"
            action="{{ route('store.brands') }}"
            method="get"
            role="search"
        >
            <label class="ag-store-visually-hidden" for="ag-store-brands-search">{{ __('agricart.storefront.brands.search_label') }}</label>
            <div class="ag-store-brands__search-shell">
                <span class="ag-store-brands__search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                        <circle cx="11" cy="11" r="7" />
                        <path d="M20 20l-3.5-3.5" />
                    </svg>
                </span>
                <input
                    id="ag-store-brands-search"
                    type="search"
                    name="q"
                    class="ag-store-brands__search-input"
                    value="{{ $searchQuery ?? '' }}"
                    placeholder="{{ __('agricart.storefront.brands.search_placeholder') }}"
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                    maxlength="80"
                    enterkeyhint="search"
                />
                <button type="submit" class="ag-store-brands__search-submit">
                    {{ __('agricart.storefront.brands.search_submit') }}
                </button>
            </div>
            @if (filled($searchQuery ?? ''))
                <a href="{{ route('store.brands') }}" class="ag-store-brands__search-clear">
                    {{ __('agricart.storefront.brands.search_clear') }}
                </a>
            @endif
        </form>

        @if (filled($searchQuery ?? ''))
            <p class="ag-store-brands__search-meta" aria-live="polite">
                @if ($brands->isEmpty())
                    {{ __('agricart.storefront.brands.search_no_results', ['query' => $searchQuery]) }}
                @else
                    {{ __('agricart.storefront.brands.search_results', ['query' => $searchQuery, 'count' => $brands->total()]) }}
                @endif
            </p>
        @endif

        @if ($brands->isEmpty() && blank($searchQuery ?? ''))
            <p class="ag-store-brands__empty">{{ __('agricart.storefront.brands.empty') }}</p>
        @elseif ($brands->isNotEmpty())
            <div class="ag-store-brands__grid">
                @foreach ($brands as $brand)
                    @php
                        $logoUrl = $brandService->imageUrl($brand);
                        $summary = $brandService->ratingSummary($brand);
                        $customerSummary = ($brandCustomerSummaries ?? [])[$brand->id] ?? ['average' => null, 'count' => 0];
                    @endphp
                    <a href="{{ route('store.brand', $brand->slug) }}" class="ag-store-brands__card">
                        <span class="ag-store-brands__card-logo">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="" width="80" height="80" loading="lazy" />
                            @else
                                <span aria-hidden="true">{{ strtoupper(mb_substr($brand->name_en, 0, 1)) }}</span>
                            @endif
                        </span>
                        <span class="ag-store-brands__card-body">
                            <strong class="ag-store-brands__card-name-en">{{ $brand->name_en }}</strong>
                            @if (filled($brand->name_ur))
                                <span class="ag-store-brands__card-name-ur" dir="rtl">{{ $brand->name_ur }}</span>
                            @endif
                            @include('store.components.brand-ratings-pair', [
                                'agricartSummary' => $summary,
                                'customerSummary' => $customerSummary,
                                'variant' => 'compact',
                            ])
                        </span>
                    </a>
                @endforeach
            </div>

            @if ($brands->hasPages())
                <div class="ag-store-pagination ag-store-brands__pagination">
                    {{ $brands->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
