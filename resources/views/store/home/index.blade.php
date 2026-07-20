@extends('store.layouts.app')

@section('title', ($settings->localized('site_name') ?? config('app.name')) . ' — ' . __('agricart.storefront.home.title'))
@section('seo_description', $settings->localized('footer_tagline') ?? __('agricart.storefront.search.empty_query'))
@section('canonical', route('store.home'))
@push('structured-data')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $settings->localized('site_name') ?? config('app.name'),
        'url' => route('store.home'),
        'inLanguage' => app()->getLocale(),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => route('store.search').'?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <section class="ag-store-howto" aria-labelledby="store-howto-heading">
        <h2 id="store-howto-heading" class="ag-store-howto__title">{{ __('agricart.storefront.home.howto_title') }}</h2>
        <ol class="ag-store-howto__steps">
            <li>{{ __('agricart.storefront.home.howto_step_1') }}</li>
            <li>{{ __('agricart.storefront.home.howto_step_2') }}</li>
            <li>{{ __('agricart.storefront.home.howto_step_3') }}</li>
            <li>{{ __('agricart.storefront.home.howto_step_4') }}</li>
        </ol>
    </section>

    @if ($settings->category_grid_enabled)
        @include('store.components.category-grid', [
            'categories' => $rootCategories,
            'sectionTitle' => $settings->localized('category_grid_title'),
            'emptyMessage' => __('agricart.storefront.categories.empty'),
        ])
    @endif
@endsection
