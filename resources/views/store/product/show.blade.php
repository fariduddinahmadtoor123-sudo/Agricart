@extends('store.layouts.app')

@section('title', $productService->localizedName($product) . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <nav class="ag-store-breadcrumb" aria-label="{{ __('agricart.storefront.category.breadcrumb') }}">
        <a href="{{ route('store.home') }}">{{ __('agricart.storefront.category.home') }}</a>
        @if ($product->category)
            <span aria-hidden="true">/</span>
            <a href="{{ route('store.category', $product->category->slug) }}">{{ $categoryService->localizedName($product->category) }}</a>
        @endif
        <span aria-hidden="true">/</span>
        <span>{{ $productService->localizedName($product) }}</span>
    </nav>

    <article class="ag-store-product-detail">
        <div class="ag-store-product-detail__gallery">
            @include('store.components.product-gallery', [
                'product' => $product,
                'imageUrls' => $imageUrls,
            ])
        </div>

        <div class="ag-store-product-detail__info">
            <h1 class="ag-store-product-detail__title">{{ $productService->localizedName($product) }}</h1>

            @if ($product->brand)
                <p class="ag-store-product-detail__brand">{{ $product->brand->name_en }}</p>
            @endif

            @if ($price = $productService->formattedRetailPrice($product))
                <p class="ag-store-product-detail__price">{{ __('agricart.storefront.products.price', ['amount' => $price]) }}</p>
            @endif

            @if ($short = $productService->localizedField($product, 'short_description'))
                <div class="ag-store-product-detail__summary">{!! nl2br(e($short)) !!}</div>
            @endif

            @if ($product->attributes->isNotEmpty())
                <dl class="ag-store-product-detail__attributes">
                    @foreach ($product->attributes as $attribute)
                        <div class="ag-store-product-detail__attribute">
                            <dt>{{ app()->getLocale() === 'ur' && filled($attribute->name_ur) ? $attribute->name_ur : $attribute->name_en }}</dt>
                            <dd>{{ $attribute->pivot->value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            @if ($product->category)
                <p class="ag-store-product-detail__back">
                    <a href="{{ route('store.category', $product->category->slug) }}">
                        {{ __('agricart.storefront.product.back_to_category', ['category' => $categoryService->localizedName($product->category)]) }}
                    </a>
                </p>
            @endif
        </div>
    </article>

    @if ($description = $productService->localizedField($product, 'full_description'))
        <section class="ag-store-product-detail__description">
            <h2 class="ag-store-section-title">{{ __('agricart.storefront.product.description') }}</h2>
            <div class="ag-store-product-detail__description-body">{!! nl2br(e($description)) !!}</div>
        </section>
    @endif
@endsection
