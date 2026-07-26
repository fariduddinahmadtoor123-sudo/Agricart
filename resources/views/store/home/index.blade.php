@extends('store.layouts.app')

@section('title', ($settings->localized('site_name') ?? config('app.name')) . ' — ' . __('agricart.storefront.home.title'))
@section('seo_description', $settings->localized('footer_tagline') ?? __('agricart.storefront.search.empty_query'))
@section('canonical', route('store.home'))
@push('structured-data')
    @php
        $schemaService = app(\App\Services\Store\StorefrontSchemaService::class);
        $homeSchema = $schemaService->homeDocument($settings, $logo_url ?: null);
    @endphp
    <script type="application/ld+json">{!! $schemaService->toJson($homeSchema) !!}</script>
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
