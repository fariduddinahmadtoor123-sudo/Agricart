@extends('store.layouts.app')

@php
    $siteName = $settings->localized('site_name') ?? config('app.name');
    $currentPage = method_exists($products, 'currentPage') ? (int) $products->currentPage() : 1;
    $pageTitle = __('agricart.storefront.products.page_title');
    if ($currentPage > 1) {
        $pageTitle .= ' — ' . __('agricart.storefront.products.page_number', ['page' => number_format($currentPage)]);
    }
@endphp

@section('title', $pageTitle . ' — ' . $siteName)
@section('seo_description', __('agricart.storefront.products.page_lead'))
@section('canonical', route('store.products'))
@section('robots', $currentPage > 1 ? 'noindex,follow' : 'index,follow,max-image-preview:large')

@section('content')
    <header class="ag-store-products-index__header">
        <h1 class="ag-store-page-title">
            {{ __('agricart.storefront.products.heading') }}
            <span class="ag-store-page-title__meta">
                {{ trans_choice('agricart.storefront.products.product_count', $totalProducts, ['count' => number_format($totalProducts)]) }}
            </span>
        </h1>
        <p class="ag-store-products-index__lead">{{ __('agricart.storefront.products.page_lead') }}</p>
    </header>

    @if ($products->isEmpty())
        <div class="ag-store-placeholder-note">
            {{ __('agricart.storefront.products.index_empty') }}
        </div>
    @else
        @include('store.components.product-grid', [
            'products' => $products,
            'sectionTitle' => '',
        ])

        @if ($products->hasPages())
            <div class="ag-store-pagination">
                {{ $products->links() }}
            </div>
        @endif
    @endif
@endsection
