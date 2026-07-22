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
                    'price' => $buyPrice['amount'] ?? $buyPrice['unit_price'],
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
        $hasSpecs = $product->brand !== null || $identityFacts !== [] || $product->attributes->isNotEmpty();
        $hasPricing = $priceTable !== [];
        $hasDescription = $contentSections !== [];
        $hasDelivery = ($deliveryOptions ?? []) !== [];
        $isActive = $product->status === 'active';
        $firstTabKey = $hasDescription
            ? 'content-0'
            : ($hasDelivery ? 'delivery' : ($hasPricing ? 'pricing' : null));
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
                        @php
                            $allowsOverselling = app(\App\Services\Sales\SaleService::class)->allowsOverselling();
                            $stockQty = $product->stock_qty;
                            $inStock = $allowsOverselling || $stockQty === null || (float) $stockQty > 0;
                        @endphp

                        @if ($inStock)
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
                                            @if (! $allowsOverselling && $stockQty !== null) max="{{ (int) max(1, (float) $stockQty) }}" @endif
                                            required
                                        />
                                        <button type="submit" class="ag-store-btn ag-store-btn--primary ag-store-pdp__add-btn">
                                            {{ __('agricart.storefront.product.add_to_cart') }}
                                        </button>
                                    </div>
                                </div>
                                @if (! $allowsOverselling && $stockQty !== null)
                                    <p class="ag-store-pdp__stock-note">{{ __('agricart.storefront.product.in_stock', ['qty' => app(\App\Services\Sales\SaleService::class)->formatQty((float) $stockQty)]) }}</p>
                                @endif
                            </form>
                        @else
                            <p class="ag-store-pdp__stock-note ag-store-pdp__stock-note--out">{{ __('agricart.storefront.product.out_of_stock') }}</p>
                            @if ($settings->whatsapp_enabled && filled($settings->whatsappChatUrl()))
                                <a
                                    href="{{ $settings->whatsappChatUrl(__('agricart.storefront.product.out_of_stock_whatsapp', ['product' => $productName])) }}"
                                    class="ag-store-btn ag-store-btn--whatsapp ag-store-btn--block"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >{{ __('agricart.storefront.product.ask_on_whatsapp') }}</a>
                            @endif
                        @endif
                    @endif
                </section>
            </div>
        </div>

        @if ($hasPricing || $hasDescription || $hasDelivery)
            <div class="ag-store-pdp__tabs" data-store-pdp-tabs>
                <div class="ag-store-pdp__tablist" role="tablist" aria-label="{{ __('agricart.storefront.product.sections.details') }}">
                    @foreach ($contentSections as $section)
                        @php($tabKey = 'content-' . $loop->index)
                        <button
                            type="button"
                            @class(['ag-store-pdp__tab', 'is-active' => $tabKey === $firstTabKey])
                            id="store-pdp-tab-{{ $tabKey }}"
                            role="tab"
                            aria-selected="{{ $tabKey === $firstTabKey ? 'true' : 'false' }}"
                            aria-controls="store-pdp-panel-{{ $tabKey }}"
                            tabindex="{{ $tabKey === $firstTabKey ? '0' : '-1' }}"
                            data-store-pdp-tab="{{ $tabKey }}"
                        >
                            {{ $section['title'] }}
                        </button>
                    @endforeach
                    @if ($hasDelivery)
                        <button
                            type="button"
                            @class(['ag-store-pdp__tab', 'is-active' => $firstTabKey === 'delivery'])
                            id="store-pdp-tab-delivery"
                            role="tab"
                            aria-selected="{{ $firstTabKey === 'delivery' ? 'true' : 'false' }}"
                            aria-controls="store-pdp-panel-delivery"
                            tabindex="{{ $firstTabKey === 'delivery' ? '0' : '-1' }}"
                            data-store-pdp-tab="delivery"
                        >
                            {{ __('agricart.storefront.product.tabs.delivery') }}
                        </button>
                    @endif
                    @if ($hasPricing)
                        <button
                            type="button"
                            @class(['ag-store-pdp__tab', 'is-active' => $firstTabKey === 'pricing'])
                            id="store-pdp-tab-pricing"
                            role="tab"
                            aria-selected="{{ $firstTabKey === 'pricing' ? 'true' : 'false' }}"
                            aria-controls="store-pdp-panel-pricing"
                            tabindex="{{ $firstTabKey === 'pricing' ? '0' : '-1' }}"
                            data-store-pdp-tab="pricing"
                        >
                            {{ __('agricart.storefront.product.tabs.pricing') }}
                        </button>
                    @endif
                </div>

                @foreach ($contentSections as $section)
                    @php($panelKey = 'content-' . $loop->index)
                    <div
                        @class(['ag-store-pdp__panel', 'is-active' => $panelKey === $firstTabKey])
                        id="store-pdp-panel-{{ $panelKey }}"
                        role="tabpanel"
                        aria-labelledby="store-pdp-tab-{{ $panelKey }}"
                        tabindex="0"
                        data-store-pdp-panel="{{ $panelKey }}"
                        @if ($panelKey !== $firstTabKey) hidden @endif
                    >
                        <div class="ag-store-pdp__prose">{!! $section['html'] !!}</div>
                    </div>
                @endforeach

                @if ($hasDelivery)
                    <div
                        @class(['ag-store-pdp__panel', 'is-active' => $firstTabKey === 'delivery'])
                        id="store-pdp-panel-delivery"
                        role="tabpanel"
                        aria-labelledby="store-pdp-tab-delivery"
                        tabindex="0"
                        data-store-pdp-panel="delivery"
                        @if ($firstTabKey !== 'delivery') hidden @endif
                    >
                        @include('store.components.product-delivery', ['deliveryOptions' => $deliveryOptions])
                    </div>
                @endif

                @if ($hasPricing)
                    <div
                        @class(['ag-store-pdp__panel', 'is-active' => $firstTabKey === 'pricing'])
                        id="store-pdp-panel-pricing"
                        role="tabpanel"
                        aria-labelledby="store-pdp-tab-pricing"
                        tabindex="0"
                        data-store-pdp-panel="pricing"
                        @if ($firstTabKey !== 'pricing') hidden @endif
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
