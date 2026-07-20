@extends('store.layouts.app')

@php
    $categoryName = $categoryService->localizedName($category);
    $categorySeoTitle = $categoryService->localizedField($category, 'seo_title') ?: $categoryName;
    $categorySeoDescription = $categoryService->localizedField($category, 'seo_description')
        ?: $categoryService->localizedField($category, 'short_description')
        ?: $categoryName;
    $categoryImage = $categoryService->imageUrl($category);
    $categorySchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                'name' => $categoryName,
                'description' => strip_tags($categorySeoDescription),
                'url' => route('store.category', $category->slug),
                'image' => $categoryImage,
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('agricart.storefront.category.home'), 'item' => route('store.home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $categoryName, 'item' => route('store.category', $category->slug)],
                ],
            ],
        ],
    ];
@endphp

@section('title', $categorySeoTitle . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', strip_tags($categorySeoDescription))
@section('canonical', route('store.category', $category->slug))
@if ($categoryImage) @section('seo_image', $categoryImage) @endif
@push('structured-data')
    <script type="application/ld+json">{!! json_encode($categorySchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <h1 class="ag-store-page-title">
        {{ $categoryName }}
        <span class="ag-store-page-title__meta">
            {{ trans_choice('agricart.storefront.categories.product_count', $categoryProductCount, ['count' => number_format($categoryProductCount)]) }}
        </span>
    </h1>

    @if ($childCategories->isNotEmpty())
        @include('store.components.category-grid', [
            'categories' => $childCategories,
            'sectionTitle' => __('agricart.storefront.category.subcategories'),
            'emptyMessage' => __('agricart.storefront.category.no_children'),
            'productCounts' => $productCounts,
        ])
    @endif

    @if ($products->isNotEmpty())
        @include('store.components.product-grid', [
            'products' => $products,
            'sectionTitle' => __('agricart.storefront.products.title'),
        ])

        @if ($products->hasPages())
            <div class="ag-store-pagination">
                {{ $products->links() }}
            </div>
        @endif
    @elseif ($childCategories->isEmpty() && ! $hasStorefrontContent)
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
