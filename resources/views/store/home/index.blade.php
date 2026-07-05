@extends('store.layouts.app')

@section('title', ($settings->localized('site_name') ?? config('app.name')) . ' — ' . __('agricart.storefront.home.title'))

@section('content')
    @if ($settings->category_grid_enabled)
        @include('store.components.category-grid', [
            'categories' => $rootCategories,
            'sectionTitle' => $settings->localized('category_grid_title'),
            'emptyMessage' => __('agricart.storefront.categories.empty'),
        ])
    @endif
@endsection
