@extends('store.layouts.app')

@section('title', __('agricart.storefront.cart.title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <h1 class="ag-store-page-title">{{ __('agricart.storefront.cart.title') }}</h1>

    @if (session('store_status'))
        <div class="ag-store-alert ag-store-alert--success" role="status">{{ session('store_status') }}</div>
    @endif

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
                        <th>{{ __('agricart.storefront.cart.col_product') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_price') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_quantity') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_total') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $line)
                        @php $p = $line['product']; @endphp
                        <tr>
                            <td class="ag-store-cart__product">
                                <a href="{{ route('store.product', $p->slug) }}" class="ag-store-cart__name">{{ app(\App\Services\Store\StoreProductService::class)->localizedName($p) }}</a>
                                @if ($p->brand)
                                    <span class="ag-store-cart__brand">{{ $p->brand->name_en }}</span>
                                @endif
                            </td>
                            <td>{{ __('agricart.storefront.products.price', ['amount' => $line['unit_price_formatted']]) }}</td>
                            <td>
                                <form method="post" action="{{ route('store.cart.update') }}" class="ag-store-cart__qty-form">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $p->id }}" />
                                    <input type="number" name="quantity" class="ag-store-cart__qty-input" value="{{ $line['quantity'] }}" min="0" step="1" />
                                    <button type="submit" class="ag-store-cart__update">{{ __('agricart.storefront.cart.update') }}</button>
                                </form>
                            </td>
                            <td>{{ __('agricart.storefront.products.price', ['amount' => $line['line_total_formatted']]) }}</td>
                            <td>
                                <form method="post" action="{{ route('store.cart.remove', $p->id) }}">
                                    @csrf
                                    <button type="submit" class="ag-store-cart__remove">{{ __('agricart.storefront.cart.remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="ag-store-cart__footer">
                <p class="ag-store-cart__subtotal">
                    <span>{{ __('agricart.storefront.cart.subtotal') }}</span>
                    <strong>{{ __('agricart.storefront.products.price', ['amount' => $subtotal]) }}</strong>
                </p>
                <div class="ag-store-cart__actions">
                    <a href="{{ route('store.home') }}" class="ag-store-btn ag-store-btn--secondary">{{ __('agricart.storefront.cart.continue_shopping') }}</a>
                    @auth('customer')
                        <a href="{{ route('store.checkout') }}" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.storefront.cart.checkout') }}</a>
                    @else
                        <a href="{{ route('store.customer.login', ['redirect' => route('store.checkout')]) }}" class="ag-store-btn ag-store-btn--primary">
                            {{ __('agricart.storefront.cart.login_to_checkout') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    @endif
@endsection
