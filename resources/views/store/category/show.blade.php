@extends('store.layouts.app')

@php
    $comingSoon = (bool) ($comingSoon ?? false);
    $categoryName = $categoryService->localizedName($category);
    $categorySeoTitle = $categoryService->localizedField($category, 'seo_title') ?: $categoryName;
    $categorySeoDescription = $categoryService->localizedField($category, 'seo_description')
        ?: $categoryService->localizedField($category, 'short_description')
        ?: $categoryName;
    $categoryImage = $categoryService->imageUrl($category);
    $schemaService = app(\App\Services\Store\StorefrontSchemaService::class);
    $categorySchema = $schemaService->categoryDocument($category, $categoryService, $faqs ?? []);
@endphp

@section('title', $categorySeoTitle . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', strip_tags($categorySeoDescription))
@section('canonical', route('store.category', $category->slug))
@if ($categoryImage) @section('seo_image', $categoryImage) @endif
@push('structured-data')
    <script type="application/ld+json">{!! $schemaService->toJson($categorySchema) !!}</script>
@endpush

@section('content')
    @include('store.components.breadcrumbs', [
        'crumbs' => $categoryService->storefrontBreadcrumbs($category),
    ])

    <h1 class="ag-store-page-title">
        {{ $categoryName }}
        <span class="ag-store-page-title__meta">
            @if ($comingSoon)
                {{ __('agricart.storefront.categories.coming_soon') }}
            @else
                {{ trans_choice('agricart.storefront.categories.product_count', $categoryProductCount, ['count' => number_format($categoryProductCount)]) }}
            @endif
        </span>
    </h1>

    @if ($comingSoon)
        <div class="ag-store-placeholder-note" role="status">
            {{ __('agricart.storefront.category.coming_soon') }}
        </div>
    @endif

    @if ($childCategories->isNotEmpty())
        @include('store.components.category-grid', [
            'categories' => $childCategories,
            'sectionTitle' => __('agricart.storefront.category.subcategories'),
            'emptyMessage' => __('agricart.storefront.category.no_children'),
            'productCounts' => $productCounts,
        ])
    @endif

    @if (! $comingSoon && ($packages ?? collect())->isNotEmpty())
        @include('store.components.package-grid', [
            'packages' => $packages,
            'packageService' => $packageService,
        ])
    @endif

    @if (! $comingSoon && $products->isNotEmpty())
        @include('store.components.product-grid', [
            'products' => $products,
            'sectionTitle' => __('agricart.storefront.products.title'),
        ])

        @if ($products->hasPages())
            <div class="ag-store-pagination">
                {{ $products->links() }}
            </div>
        @endif
    @elseif (! $comingSoon && $childCategories->isEmpty() && ! $hasStorefrontContent)
        <div class="ag-store-placeholder-note">
            {{ __('agricart.storefront.category.coming_soon') }}
        </div>
    @endif

    @if ($hasStorefrontContent)
        @include('store.components.category-info', [
            'contentBlocks' => $contentBlocks,
            'faqs' => $faqs,
            'hsCode' => $category->hs_code,
        ])
    @endif
@endsection
