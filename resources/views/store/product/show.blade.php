@extends('store.layouts.app')

@php
    $productName = $productService->localizedName($product);
    $productSeoTitle = $productService->localizedField($product, 'seo_title') ?: $productName;
    $productSeoDescription = $productService->localizedField($product, 'seo_description')
        ?: $productService->localizedField($product, 'short_description')
        ?: $productName;
    $productSchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Product',
                'name' => $productName,
                'description' => strip_tags($productSeoDescription),
                'sku' => $product->sku ?: $product->code,
                'image' => $imageUrls,
                'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $productService->localizedBrandName($product)] : null,
                'offers' => $buyPrice ? [
                    '@type' => 'Offer',
                    'url' => route('store.product', $product->slug),
                    'priceCurrency' => 'PKR',
                    'price' => $buyPrice['price'] ?? $buyPrice['formatted'],
                    'availability' => (float) ($product->stock_qty ?? 0) > 0
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                ] : null,
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_values(array_filter([
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('agricart.storefront.category.home'), 'item' => route('store.home')],
                    $product->category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $categoryService->localizedName($product->category), 'item' => route('store.category', $product->category->slug)] : null,
                    ['@type' => 'ListItem', 'position' => $product->category ? 3 : 2, 'name' => $productName, 'item' => route('store.product', $product->slug)],
                ])),
            ],
        ],
    ];
@endphp

@section('title', $productSeoTitle . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', strip_tags($productSeoDescription))
@section('canonical', route('store.product', $product->slug))
@section('seo_type', 'product')
@if ($imageUrls !== []) @section('seo_image', $imageUrls[0]) @endif
@push('structured-data')
    <script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <nav class="ag-store-breadcrumb ag-store-breadcrumb--compact" aria-label="{{ __('agricart.storefront.category.breadcrumb') }}">
        <a href="{{ route('store.home') }}">{{ __('agricart.storefront.category.home') }}</a>
        @if ($product->category)
            <span aria-hidden="true">/</span>
            <a href="{{ route('store.category', $product->category->slug) }}">{{ $categoryService->localizedName($product->category) }}</a>
        @endif
    </nav>

    @if ($errors->any())
        <div class="ag-store-alert ag-store-alert--error" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $hasSpecs = $product->brand !== null || $identityFacts !== [] || $product->attributes->isNotEmpty() || $product->controls->isNotEmpty();
        $hasPricing = $priceTable !== [];
        $hasDescription = $contentSections !== [];
        $isActive = $product->status === 'active';
    @endphp

    <article class="ag-store-pdp">
        <div class="ag-store-pdp__hero">
            <div class="ag-store-pdp__gallery">
                @include('store.components.product-gallery', [
                    'product' => $product,
                    'imageUrls' => $imageUrls,
                ])
            </div>

            <div class="ag-store-pdp__side-panel ag-store-pdp__buy-card">
                <h1 class="ag-store-pdp__title">{{ $productName }}</h1>

                @if (! $isActive)
                    <p class="ag-store-pdp__status ag-store-pdp__status--deactivated">
                        {{ __('agricart.storefront.product.deactivated') }}
                    </p>
                @endif

                <section class="ag-store-pdp__side-section ag-store-pdp__side-section--specs">
                    <h2 class="ag-store-pdp__side-section-title">{{ __('agricart.storefront.product.tabs.specifications') }}</h2>
                    @include('store.components.product-specs', [
                        'product' => $product,
                        'presenter' => $presenter,
                        'identityFacts' => $identityFacts,
                    ])
                </section>

                <section class="ag-store-pdp__side-section ag-store-pdp__side-section--buy">
                    @if ($buyPrice)
                        <div class="ag-store-pdp__price-box">
                            <span class="ag-store-pdp__price">{{ __('agricart.storefront.products.price', ['amount' => $buyPrice['formatted']]) }}</span>
                            <span class="ag-store-pdp__price-tier">{{ $buyPrice['tier_label'] }}</span>
                        </div>
                    @elseif (! ($onlineStoreChannel['show_prices'] ?? true))
                        <p class="ag-store-pdp__offline-msg">{{ $onlineStoreChannel['offline_message'] ?? __('sales_channels.defaults.offline_message') }}</p>
                    @endif

                    @if ($short = $productService->localizedField($product, 'short_description'))
                        <p class="ag-store-pdp__excerpt ag-store-pdp__excerpt--side">{!! nl2br(e($short)) !!}</p>
                    @endif

                    @if ($isActive && ($onlineStoreChannel['allow_purchase'] ?? true))
                        <form method="post" action="{{ route('store.cart.add') }}" class="ag-store-pdp__cart-form ag-store-pdp__cart-form--side">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}" />
                            <div class="ag-store-pdp__qty-row">
                                <label class="ag-store-pdp__qty-label" for="product-qty">{{ __('agricart.storefront.product.quantity') }}</label>
                                <div class="ag-store-pdp__qty-controls">
                                    <input
                                        id="product-qty"
                                        type="number"
                                        name="quantity"
                                        class="ag-store-pdp__qty-input"
                                        value="1"
                                        min="1"
                                        step="1"
                                        required
                                    />
                                    <button type="submit" class="ag-store-btn ag-store-btn--primary ag-store-pdp__add-btn">
                                        {{ __('agricart.storefront.product.add_to_cart') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </section>
            </div>
        </div>

        @if ($hasPricing || $hasDescription)
            <div class="ag-store-pdp__tabs" data-store-pdp-tabs>
                <div class="ag-store-pdp__tablist" role="tablist" aria-label="{{ __('agricart.storefront.product.sections.details') }}">
                    @foreach ($contentSections as $section)
                        @php($tabKey = 'content-' . $loop->index)
                        <button
                            type="button"
                            @class(['ag-store-pdp__tab', 'is-active' => $loop->first])
                            id="store-pdp-tab-{{ $tabKey }}"
                            role="tab"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            aria-controls="store-pdp-panel-{{ $tabKey }}"
                            tabindex="{{ $loop->first ? '0' : '-1' }}"
                            data-store-pdp-tab="{{ $tabKey }}"
                        >
                            {{ $section['title'] }}
                        </button>
                    @endforeach
                    @if ($hasPricing)
                        <button
                            type="button"
                            @class(['ag-store-pdp__tab', 'is-active' => ! $hasDescription])
                            id="store-pdp-tab-pricing"
                            role="tab"
                            aria-selected="{{ $hasDescription ? 'false' : 'true' }}"
                            aria-controls="store-pdp-panel-pricing"
                            tabindex="{{ $hasDescription ? '-1' : '0' }}"
                            data-store-pdp-tab="pricing"
                        >
                            {{ __('agricart.storefront.product.tabs.pricing') }}
                        </button>
                    @endif
                </div>

                @foreach ($contentSections as $section)
                    @php($panelKey = 'content-' . $loop->index)
                    <div
                        @class(['ag-store-pdp__panel', 'is-active' => $loop->first])
                        id="store-pdp-panel-{{ $panelKey }}"
                        role="tabpanel"
                        aria-labelledby="store-pdp-tab-{{ $panelKey }}"
                        tabindex="0"
                        data-store-pdp-panel="{{ $panelKey }}"
                        @if (! $loop->first) hidden @endif
                    >
                        <div class="ag-store-pdp__prose">{!! $section['html'] !!}</div>
                    </div>
                @endforeach

                @if ($hasPricing)
                    <div
                        @class(['ag-store-pdp__panel', 'is-active' => ! $hasDescription])
                        id="store-pdp-panel-pricing"
                        role="tabpanel"
                        aria-labelledby="store-pdp-tab-pricing"
                        tabindex="0"
                        data-store-pdp-panel="pricing"
                        @if ($hasDescription) hidden @endif
                    >
                        <div class="ag-store-pdp__table-wrap">
                            <table class="ag-store-pdp__price-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('agricart.storefront.product.price_table.min_qty') }}</th>
                                        <th>{{ __('agricart.storefront.product.price_table.tier') }}</th>
                                        <th>{{ __('agricart.storefront.product.price_table.unit_price') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($priceTable as $row)
                                        <tr>
                                            <td>{{ $row['min_qty'] }}+</td>
                                            <td>{{ $row['tier_label'] }}</td>
                                            <td>{{ __('agricart.storefront.products.price', ['amount' => $row['formatted']]) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </article>
@endsection
