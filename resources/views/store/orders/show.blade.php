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
                    {{ $order->order_date?->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'ur' ? 'l، d M Y' : 'l, d M Y') }}
                </p>
                @if ($statusKey === 'converted')
                    <p class="ag-store-orders__invoice-note">{{ __('agricart.customer.orders.invoice_issued_note') }}</p>
                @endif
                @if ($order->queued_at !== null || filled($order->fulfillment_status))
                    <section class="ag-store-orders__detail-card ag-store-orders__fulfillment">
                        <h2 class="ag-store-orders__section-title">{{ __('agricart.customer.orders.fulfillment_heading') }}</h2>
                        <dl class="ag-store-orders__meta ag-store-orders__meta--grid">
                            <div>
                                <dt>{{ __('agricart.customer.orders.fulfillment_label') }}</dt>
                                <dd>{{ __('order_dispatch.fulfillment_statuses.' . ($order->fulfillment_status ?: 'awaiting_assignment')) }}</dd>
                            </div>
                        </dl>
                        @php $parcels = $order->customerShippingParcels(); @endphp
                        @if ($parcels !== [])
                            <h3 class="ag-store-orders__section-title">{{ __('agricart.customer.orders.tracking_heading') }}</h3>
                            <ul class="ag-store-orders__tracking-list">
                                @foreach ($parcels as $parcel)
                                    <li class="ag-store-orders__tracking-item">
                                        <dl class="ag-store-orders__meta ag-store-orders__meta--grid">
                                            @if (filled($parcel['carrier']))
                                                <div>
                                                    <dt>{{ __('agricart.customer.orders.carrier') }}</dt>
                                                    <dd>{{ $parcel['carrier'] }}</dd>
                                                </div>
                                            @endif
                                            @if (filled($parcel['tracking']))
                                                <div>
                                                    <dt>{{ __('agricart.customer.orders.tracking_number') }}</dt>
                                                    <dd><code>{{ $parcel['tracking'] }}</code></dd>
                                                </div>
                                            @endif
                                            @if (filled($parcel['bilty']))
                                                <div>
                                                    <dt>{{ __('agricart.customer.orders.bilty') }}</dt>
                                                    <dd><code>{{ $parcel['bilty'] }}</code></dd>
                                                </div>
                                            @endif
                                        </dl>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endif
            </header>

            @if (session('customer_auth_status'))
                <div @class([
                    'ag-store-customer-auth__alert',
                    'is-success' => session('customer_auth_status_type', 'success') === 'success',
                    'is-danger' => session('customer_auth_status_type') === 'danger',
                ]) role="alert">
                    {{ session('customer_auth_status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="ag-store-customer-auth__alert is-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

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
                    @if (filled($order->localizedDeliveryMethodName()))
                        <div>
                            <dt>{{ __('agricart.storefront.checkout.delivery_method') }}</dt>
                            <dd>{{ $order->localizedDeliveryMethodName() }}</dd>
                        </div>
                    @endif
                    @if (filled($order->ship_address))
                        <div class="ag-store-orders__meta-span">
                            <dt>{{ __('agricart.customer.fields.address_line1') }}</dt>
                            <dd>{{ $order->ship_address }}</dd>
                        </div>
                    @endif
                    @if (filled($order->localizedDeliveryNotice()))
                        <div class="ag-store-orders__meta-span">
                            <dt>{{ __('agricart.storefront.checkout.delivery_notice') }}</dt>
                            <dd>{{ $order->localizedDeliveryNotice() }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="ag-store-orders__detail-card">
                <h2 class="ag-store-orders__section-title">{{ __('agricart.storefront.checkout.order_summary') }}</h2>
                <ul class="ag-store-orders__lines">
                    @foreach ($order->items as $index => $item)
                        @include('store.orders.partials.line-item', [
                            'item' => $item,
                            'index' => $index,
                            'sales' => $sales,
                            'order' => $order,
                            'productReview' => ($reviewsByProduct ?? collect())->get($item->product_id),
                        ])
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
                    @if (filled($order->customer_payment_reference))
                        <p class="ag-store-orders__note">
                            <strong>{{ __('agricart.storefront.checkout.payment_reference') }}:</strong>
                            {{ $order->customer_payment_reference }}
                        </p>
                    @endif
                    @if (filled($order->customer_payment_note))
                        <p class="ag-store-orders__note">{{ $order->customer_payment_note }}</p>
                    @endif
                    @if (filled($order->customer_payment_proof_status) && $order->customer_payment_proof_status !== \App\Models\OnlineSaleOrder::PROOF_NONE)
                        <p class="ag-store-orders__proof-status">
                            {{ __('online_sale_orders.payment_proof_statuses.' . $order->customer_payment_proof_status) }}
                        </p>
                    @endif
                    @if (filled($order->payment_proof_rejection_note))
                        <p class="ag-store-orders__note ag-store-orders__note--reject">{{ $order->payment_proof_rejection_note }}</p>
                    @endif
                </section>
            @endif

            @if ($order->requiresCustomerPaymentProof())
                <section class="ag-store-orders__detail-card ag-store-orders__payment-proof">
                    <h2 class="ag-store-orders__section-title">{{ __('agricart.customer.orders.upload_receipt_heading') }}</h2>
                    <p class="ag-store-customer-auth__lead">{{ __('agricart.customer.orders.upload_receipt_lead') }}</p>

                    @include('store.components.bank-transfer-accounts', [
                        'accounts' => $bankTransferAccounts ?? collect(),
                        'lead' => __('agricart.storefront.checkout.bank_payment_accounts_lead'),
                    ])

                    @if ($order->customerPaymentProofUrl())
                        <p class="ag-store-orders__note">
                            <a href="{{ $order->customerPaymentProofUrl() }}" class="ag-store-orders__link" target="_blank" rel="noopener">
                                {{ __('agricart.customer.orders.view_uploaded_receipt') }}
                            </a>
                        </p>
                    @endif

                    <div class="ag-store-orders__proof-options">
                        <div class="ag-store-orders__proof-option">
                            <h3 class="ag-store-orders__proof-option-title">{{ __('agricart.customer.orders.proof_upload_title') }}</h3>
                            <p class="ag-store-orders__proof-option-text">{{ __('agricart.customer.orders.proof_upload_text') }}</p>

                            <form method="post" action="{{ route('store.customer.orders.payment-proof', $order->order_number) }}" enctype="multipart/form-data" class="ag-store-checkout__form">
                                @csrf
                                <label class="ag-store-checkout__field">
                                    <span class="ag-store-checkout__label">{{ __('agricart.storefront.checkout.payment_reference') }}</span>
                                    <input
                                        type="text"
                                        name="customer_payment_reference"
                                        class="ag-store-checkout__input"
                                        value="{{ old('customer_payment_reference', $order->customer_payment_reference) }}"
                                        autocomplete="off"
                                    />
                                </label>
                                <label class="ag-store-checkout__field">
                                    <span class="ag-store-checkout__label">{{ __('agricart.customer.orders.bank_receipt_file') }}</span>
                                    <input type="file" name="bank_receipt" class="ag-store-checkout__input" accept="image/jpeg,image/png,image/webp,application/pdf" />
                                    <span class="ag-store-orders__file-hint">{{ __('agricart.customer.orders.bank_receipt_hint') }}</span>
                                </label>
                                <button type="submit" class="ag-store-btn ag-store-btn--primary ag-store-btn--block">{{ __('agricart.customer.orders.submit_payment_proof') }}</button>
                            </form>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
