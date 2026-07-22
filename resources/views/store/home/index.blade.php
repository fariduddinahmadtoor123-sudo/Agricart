@extends('store.layouts.app')

@section('title', ($settings->localized('site_name') ?? config('app.name')) . ' — ' . __('agricart.storefront.home.title'))
@section('seo_description', $settings->localized('footer_tagline') ?? __('agricart.storefront.search.empty_query'))
@section('canonical', route('store.home'))
@push('structured-data')
    @php
        $organization = array_filter([
            '@type' => 'Organization',
            'name' => $settings->localized('site_name') ?? config('app.name'),
            'url' => route('store.home'),
            'logo' => $logo_url ?: null,
            'telephone' => $settings->top_bar_phone ?: ($settings->whatsapp_number ?: null),
            'sameAs' => array_values(array_filter([
                $settings->social_facebook ?? null,
                $settings->social_instagram ?? null,
                $settings->social_youtube ?? null,
                $settings->social_whatsapp ?? null,
            ])),
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');

        $homeSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                $organization,
                [
                    '@type' => 'WebSite',
                    'name' => $settings->localized('site_name') ?? config('app.name'),
                    'url' => route('store.home'),
                    'inLanguage' => app()->getLocale(),
                    'publisher' => ['@type' => 'Organization', 'name' => $settings->localized('site_name') ?? config('app.name')],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('store.search').'?q={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($homeSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    @if ($settings->category_grid_enabled)
        @include('store.components.category-grid', [
            'categories' => $rootCategories,
            'sectionTitle' => $settings->localized('category_grid_title'),
            'emptyMessage' => __('agricart.storefront.categories.empty'),
        ])
    @endif
@endsection
