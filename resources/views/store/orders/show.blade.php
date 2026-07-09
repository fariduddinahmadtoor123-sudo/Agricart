@extends('store.layouts.app')

@section('title', __('agricart.customer.orders.detail_title', ['number' => $order->order_number]) . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    @php
        $statusKey = $order->order_status;
        $statusLabel = __('online_sale_orders.order_statuses.' . $statusKey);
    @endphp
    <div class="ag-store-customer-auth-page">
        <div class="ag-store-account ag-store-account--orders">
            <header class="ag-store-orders__detail-head">
                <p class="ag-store-orders__back">
                    <a href="{{ route('store.customer.orders') }}" class="ag-store-orders__link">{{ __('agricart.customer.orders.back_to_list') }}</a>
                </p>
                <div class="ag-store-orders__detail-title-row">
                    <h1 class="ag-store-customer-auth__title">{{ __('agricart.customer.orders.detail_heading', ['number' => $order->order_number]) }}</h1>
                    <span @class([
                        'ag-store-orders__status',
                        'is-pending' => $statusKey === 'pending',
                        'is-paid' => in_array($statusKey, ['payment_received', 'processing'], true),
                        'is-done' => $statusKey === 'converted',
                        'is-cancelled' => $statusKey === 'cancelled',
                    ])>{{ $statusLabel }}</span>
                </div>
                <p class="ag-store-customer-auth__lead ag-store-orders__detail-date">
                    {{ $order->order_date?->format('l, d M Y') }}
                </p>
            </header>

            <section class="ag-store-orders__detail-card">
                <h2 class="ag-store-orders__section-title">{{ __('agricart.customer.orders.shipping') }}</h2>
                <dl class="ag-store-orders__meta ag-store-orders__meta--grid">
                    <div>
                        <dt>{{ __('agricart.storefront.checkout.ship_contact_name') }}</dt>
                        <dd>{{ $order->ship_contact_name }}</dd>
                    </div>
                    @if (filled($order->ship_phone))
                        <div>
                            <dt>{{ __('agricart.customer.fields.phone') }}</dt>
                            <dd>{{ $order->ship_phone }}</dd>
                        </div>
                    @endif
                    @if (filled($order->ship_city))
                        <div>
                            <dt>{{ __('agricart.customer.fields.city') }}</dt>
                            <dd>{{ $order->ship_city }}</dd>
                        </div>
                    @endif
                    @if (filled($order->ship_address))
                        <div class="ag-store-orders__meta-span">
                            <dt>{{ __('agricart.customer.fields.address_line1') }}</dt>
                            <dd>{{ $order->ship_address }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="ag-store-orders__detail-card">
                <h2 class="ag-store-orders__section-title">{{ __('agricart.storefront.checkout.order_summary') }}</h2>
                <ul class="ag-store-orders__lines">
                    @foreach ($order->items as $index => $item)
                        @include('store.orders.partials.line-item', ['item' => $item, 'index' => $index, 'sales' => $sales])
                    @endforeach
                </ul>
                <div class="ag-store-orders__totals">
                    <div class="ag-store-orders__totals-row">
                        <span>{{ __('agricart.storefront.cart.subtotal') }}</span>
                        <strong>{{ __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $order->subtotal)]) }}</strong>
                    </div>
                    <div class="ag-store-orders__totals-row ag-store-orders__totals-row--grand">
                        <span>{{ __('agricart.customer.orders.total') }}</span>
                        <strong>{{ __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $order->total_amount)]) }}</strong>
                    </div>
                </div>
            </section>

            @if (filled($order->customer_payment_method))
                <section class="ag-store-orders__detail-card">
                    <h2 class="ag-store-orders__section-title">{{ __('agricart.storefront.checkout.payment_method') }}</h2>
                    <p class="ag-store-orders__payment-method">{{ __('pos.payment_methods.' . $order->customer_payment_method) }}</p>
                    @if (filled($order->customer_payment_note))
                        <p class="ag-store-orders__note">{{ $order->customer_payment_note }}</p>
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection
