@extends('store.layouts.app')

@section('title', $categoryService->localizedName($category) . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    @if ($childCategories->isNotEmpty())
        @include('store.components.category-grid', [
            'categories' => $childCategories,
            'sectionTitle' => null,
            'emptyMessage' => __('agricart.storefront.category.no_children'),
        ])
    @elseif (! $hasStorefrontContent)
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
