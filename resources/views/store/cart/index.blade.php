@extends('store.layouts.app')

@section('title', __('agricart.storefront.cart.title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <h1 class="ag-store-page-title">{{ __('agricart.storefront.cart.title') }}</h1>

    @if ($errors->any())
        <div class="ag-store-alert ag-store-alert--error" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($lines === [])
        <div class="ag-store-empty">{{ __('agricart.storefront.cart.empty') }}</div>
        <p><a href="{{ route('store.home') }}" class="ag-store-btn ag-store-btn--secondary">{{ __('agricart.storefront.cart.continue_shopping') }}</a></p>
    @else
        <div class="ag-store-cart">
            <table class="ag-store-cart__table">
                <thead>
                    <tr>
                        <th class="ag-store-cart__col-sn">{{ __('agricart.storefront.cart.col_sn') }}</th>
                        <th class="ag-store-cart__col-thumb">{{ __('agricart.storefront.cart.col_image') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_product') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_price') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_quantity') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_total') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $index => $line)
                        @php
                            $p = $line['product'];
                            $thumbUrl = $productService->imageUrl($p);
                        @endphp
                        <tr>
                            <td class="ag-store-cart__sn">{{ $index + 1 }}</td>
                            <td class="ag-store-cart__thumb">
                                <a href="{{ route('store.product', $p->slug) }}" class="ag-store-cart__thumb-link">
                                    @if ($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="{{ $productService->localizedName($p) }}" class="ag-store-cart__thumb-img" loading="lazy" decoding="async" width="56" height="56" />
                                    @else
                                        <span class="ag-store-cart__thumb-placeholder">{{ mb_strtoupper(mb_substr($productService->localizedName($p), 0, 1)) }}</span>
                                    @endif
                                </a>
                            </td>
                            <td class="ag-store-cart__product">
                                <a href="{{ route('store.product', $p->slug) }}" class="ag-store-cart__name">{{ $productService->localizedName($p) }}</a>
                                @if ($p->brand)
                                    <span class="ag-store-cart__brand">{{ $productService->localizedBrandName($p) }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('agricart.storefront.cart.col_price') }}">{{ __('agricart.storefront.products.price', ['amount' => $line['unit_price_formatted']]) }}</td>
                            <td data-label="{{ __('agricart.storefront.cart.col_quantity') }}">
                                <form method="post" action="{{ route('store.cart.update') }}" class="ag-store-cart__qty-form">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $p->id }}" />
                                    <input
                                        type="number"
                                        name="quantity"
                                        class="ag-store-cart__qty-input"
                                        value="{{ $line['quantity'] }}"
                                        min="0"
                                        step="1"
                                        aria-label="{{ __('agricart.storefront.cart.col_quantity') }}: {{ $productService->localizedName($p) }}"
                                    />
                                    <button type="submit" class="ag-store-cart__update">{{ __('agricart.storefront.cart.update') }}</button>
                                </form>
                            </td>
                            <td data-label="{{ __('agricart.storefront.cart.col_total') }}">{{ __('agricart.storefront.products.price', ['amount' => $line['line_total_formatted']]) }}</td>
                            <td data-label="{{ __('agricart.storefront.cart.remove') }}">
                                <form method="post" action="{{ route('store.cart.remove', $p->id) }}">
                                    @csrf
                                    <button type="submit" class="ag-store-cart__remove">{{ __('agricart.storefront.cart.remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="ag-store-cart__footer" style="text-align: end;">
                <div class="ag-store-cart__summary" style="width: 100%; max-width: 28rem; margin-inline-start: auto;">
                    <p class="ag-store-cart__subtotal">
                        <span>{{ __('agricart.storefront.cart.subtotal') }}</span>
                        <strong>{{ __('agricart.storefront.products.price', ['amount' => $subtotal]) }}</strong>
                    </p>
                    <div class="ag-store-cart__actions" dir="ltr">
                        <a href="{{ route('store.cart.print') }}" target="_blank" rel="noopener" class="ag-store-btn ag-store-btn--secondary ag-store-cart__print">
                            {{ __('agricart.storefront.cart.print') }}
                        </a>
                        <a href="{{ route('store.home') }}" class="ag-store-btn ag-store-btn--secondary">{{ __('agricart.storefront.cart.continue_shopping') }}</a>
                        @auth('customer')
                            <a href="{{ route('store.checkout') }}" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.storefront.cart.checkout') }}</a>
                        @else
                            <a href="{{ route('store.customer.login', ['redirect' => route('store.checkout', absolute: false)]) }}" class="ag-store-btn ag-store-btn--primary">
                                {{ __('agricart.storefront.cart.login_to_checkout') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
