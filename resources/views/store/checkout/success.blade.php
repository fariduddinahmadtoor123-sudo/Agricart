@extends('store.layouts.app')

@section('title', __('agricart.storefront.checkout.success_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-checkout-success">
        <h1 class="ag-store-page-title">{{ __('agricart.storefront.checkout.success_title') }}</h1>
        <p class="ag-store-checkout-success__lead">{{ __('agricart.storefront.checkout.success_lead') }}</p>
        <p class="ag-store-checkout-success__order">
            {{ __('agricart.storefront.checkout.order_number') }}
            <strong><code>{{ $orderNumber }}</code></strong>
        </p>
        <p class="ag-store-checkout-success__hint">{{ __('agricart.storefront.checkout.success_hint') }}</p>
        @isset($order)
            @if (filled($order->localizedDeliveryMethodName()))
                <p class="ag-store-checkout-success__hint">
                    {{ __('agricart.storefront.checkout.delivery_method') }}:
                    <strong>{{ $order->localizedDeliveryMethodName() }}</strong>
                </p>
            @endif
            @if ($order->customer_payment_method === 'bank_transfer' && $order->needsBankReceiptUpload())
                <p class="ag-store-checkout-success__bank-notice">{{ __('agricart.storefront.checkout.success_upload_receipt') }}</p>
            @endif
        @endisset
        <div class="ag-store-checkout-success__actions">
            @auth('customer')
                <a href="{{ route('store.customer.orders.show', $orderNumber) }}" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.customer.orders.view_this_order') }}</a>
            @endauth
            <a href="{{ route('store.home') }}" class="ag-store-btn ag-store-btn--secondary">{{ __('agricart.storefront.cart.continue_shopping') }}</a>
        </div>
    </div>
@endsection
