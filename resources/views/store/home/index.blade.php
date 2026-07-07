@extends('store.layouts.app')

@section('title', ($settings->localized('site_name') ?? config('app.name')) . ' — ' . __('agricart.storefront.home.title'))

@section('content')
    <section class="ag-store-home-cta">
        <div class="ag-store-home-cta__inner">
            <h2 class="ag-store-home-cta__title">{{ __('agricart.storefront.home.staff_cta_title') }}</h2>
            <p class="ag-store-home-cta__text">{{ __('agricart.storefront.home.staff_cta_text') }}</p>
            <a href="{{ $settings->resolvedRegisterUrl() }}" class="ag-store-home-cta__btn">{{ __('agricart.storefront.home.staff_cta_button') }}</a>
        </div>
    </section>

    @if ($settings->category_grid_enabled)
        @include('store.components.category-grid', [
            'categories' => $rootCategories,
            'sectionTitle' => $settings->localized('category_grid_title'),
            'emptyMessage' => __('agricart.storefront.categories.empty'),
        ])
    @endif
@endsection
