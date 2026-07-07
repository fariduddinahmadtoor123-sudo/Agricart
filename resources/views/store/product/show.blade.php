@extends('store.layouts.app')

@section('title', $productService->localizedName($product) . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

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

    <article class="ag-store-pdp">
        <div class="ag-store-pdp__hero">
            <div class="ag-store-pdp__gallery">
                @include('store.components.product-gallery', [
                    'product' => $product,
                    'imageUrls' => $imageUrls,
                ])
            </div>

            <div class="ag-store-pdp__buy-card">
                @if ($brand = $presenter->localizedBrandName($product))
                    <p class="ag-store-pdp__brand">{{ $brand }}</p>
                @endif

                <h1 class="ag-store-pdp__title">{{ $productService->localizedName($product) }}</h1>

                @php
                    $stockQty = $product->stock_qty !== null ? (float) $product->stock_qty : null;
                @endphp
                @if ($stockQty !== null)
                    <p @class([
                        'ag-store-pdp__stock',
                        'is-in-stock' => $stockQty > 0,
                        'is-out' => $stockQty <= 0,
                    ])>
                        {{ $stockQty > 0 ? __('agricart.storefront.product.in_stock') : __('agricart.storefront.product.out_of_stock') }}
                    </p>
                @endif

                @if ($buyPrice)
                    <div class="ag-store-pdp__price-box">
                        <span class="ag-store-pdp__price">{{ __('agricart.storefront.products.price', ['amount' => $buyPrice['formatted']]) }}</span>
                        <span class="ag-store-pdp__price-tier">{{ $buyPrice['tier_label'] }}</span>
                    </div>
                @endif

                @if ($short = $productService->localizedField($product, 'short_description'))
                    <p class="ag-store-pdp__excerpt">{!! nl2br(e($short)) !!}</p>
                @endif

                <form method="post" action="{{ route('store.cart.add') }}" class="ag-store-pdp__cart-form">
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
            </div>
        </div>

        @php
            $hasSpecs = $identityFacts !== [] || $product->attributes->isNotEmpty() || $product->controls->isNotEmpty();
            $hasPricing = $priceTable !== [];
            $hasDescription = $contentSections !== [];
        @endphp

        @if ($hasSpecs || $hasPricing || $hasDescription)
            <div class="ag-store-pdp__tabs" data-store-pdp-tabs>
                <div class="ag-store-pdp__tablist" role="tablist">
                    @if ($hasDescription)
                        <button type="button" class="ag-store-pdp__tab is-active" role="tab" aria-selected="true" data-store-pdp-tab="description">
                            {{ __('agricart.storefront.product.tabs.description') }}
                        </button>
                    @endif
                    @if ($hasSpecs)
                        <button type="button" @class(['ag-store-pdp__tab', 'is-active' => ! $hasDescription]) role="tab" aria-selected="{{ $hasDescription ? 'false' : 'true' }}" data-store-pdp-tab="specs">
                            {{ __('agricart.storefront.product.tabs.specifications') }}
                        </button>
                    @endif
                    @if ($hasPricing)
                        <button type="button" class="ag-store-pdp__tab" role="tab" aria-selected="false" data-store-pdp-tab="pricing">
                            {{ __('agricart.storefront.product.tabs.pricing') }}
                        </button>
                    @endif
                </div>

                @if ($hasDescription)
                    <div class="ag-store-pdp__panel is-active" role="tabpanel" data-store-pdp-panel="description">
                        @foreach ($contentSections as $section)
                            <div class="ag-store-pdp__panel-block">
                                @if (count($contentSections) > 1)
                                    <h3 class="ag-store-pdp__panel-heading">{{ $section['title'] }}</h3>
                                @endif
                                <div class="ag-store-pdp__prose">{!! $section['html'] !!}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($hasSpecs)
                    <div @class(['ag-store-pdp__panel', 'is-active' => ! $hasDescription]) role="tabpanel" data-store-pdp-panel="specs">
                        <dl class="ag-store-pdp__spec-grid">
                            @foreach ($identityFacts as $fact)
                                <div class="ag-store-pdp__spec-row">
                                    <dt>{{ $fact['label'] }}</dt>
                                    <dd>{{ $fact['value'] }}</dd>
                                </div>
                            @endforeach
                            @foreach ($product->attributes as $attribute)
                                <div class="ag-store-pdp__spec-row">
                                    <dt>{{ app()->getLocale() === 'ur' && filled($attribute->name_ur) ? $attribute->name_ur : $attribute->name_en }}</dt>
                                    <dd>{{ $attribute->pivot->value }}</dd>
                                </div>
                            @endforeach
                            @foreach ($product->controls as $control)
                                <div class="ag-store-pdp__spec-row">
                                    <dt>{{ $presenter->localizedControlName($control) }}</dt>
                                    <dd>{{ $presenter->localizedControlValue($control) ?: '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                @if ($hasPricing)
                    <div class="ag-store-pdp__panel" role="tabpanel" data-store-pdp-panel="pricing">
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
