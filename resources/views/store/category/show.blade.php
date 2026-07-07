@extends('store.layouts.app')

@section('title', $categoryService->localizedName($category) . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    @if ($childCategories->isNotEmpty())
        @include('store.components.category-grid', [
            'categories' => $childCategories,
            'sectionTitle' => __('agricart.storefront.category.subcategories'),
            'emptyMessage' => __('agricart.storefront.category.no_children'),
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
