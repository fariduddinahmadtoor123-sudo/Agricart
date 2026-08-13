@extends('store.layouts.app')

@section('title', __('agricart.storefront.checkout.success_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    @php
        $needsProof = isset($order)
            && $order->customer_payment_method === 'bank_transfer'
            && $order->needsBankReceiptUpload();
        $orderTotal = isset($order, $sales)
            ? __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $order->total_amount)])
            : null;
        $canUploadHere = $needsProof && auth('customer')->check();
    @endphp

    <div @class([
        'ag-store-checkout-success',
        'ag-store-checkout-success--wide' => $needsProof,
    ])>
        <div class="ag-store-checkout-success__card">
            <div class="ag-store-checkout-success__hero">
                <div class="ag-store-checkout-success__badge" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="square" stroke-linejoin="miter">
                        <path d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 class="ag-store-checkout-success__title">{{ __('agricart.storefront.checkout.success_title') }}</h1>
                <p class="ag-store-checkout-success__lead">{{ __('agricart.storefront.checkout.success_lead') }}</p>

                <p class="ag-store-checkout-success__hint">{{ __('agricart.storefront.checkout.success_hint') }}</p>

                @isset($order)
                    @if (filled($order->localizedDeliveryMethodName()))
                        <p class="ag-store-checkout-success__meta">
                            <span>{{ __('agricart.storefront.checkout.delivery_method') }}</span>
                            <strong>{{ $order->localizedDeliveryMethodName() }}</strong>
                        </p>
                    @endif
                @endisset
            </div>

            <div class="ag-store-checkout-success__order-box">
                <div class="ag-store-checkout-success__order-item">
                    <span class="ag-store-checkout-success__order-label">{{ __('agricart.storefront.checkout.order_number') }}</span>
                    <div class="ag-store-checkout-success__order-row">
                        <strong class="ag-store-checkout-success__order-code">{{ $orderNumber }}</strong>
                        <button
                            type="button"
                            class="ag-store-bank-accounts__copy"
                            data-copy-value="{{ $orderNumber }}"
                            data-copy-success="{{ __('agricart.storefront.accessibility.copied') }}"
                        >{{ __('agricart.storefront.accessibility.copy') }}</button>
                    </div>
                </div>
                @if ($orderTotal)
                    <div class="ag-store-checkout-success__order-item">
                        <span class="ag-store-checkout-success__amount-label">{{ __('agricart.storefront.checkout.success_amount_due') }}</span>
                        <strong class="ag-store-checkout-success__amount">{{ $orderTotal }}</strong>
                    </div>
                @endif
            </div>

            @if ($needsProof)
                <div class="ag-store-checkout-success__grid">
                    <section class="ag-store-checkout-success__pay-next" aria-labelledby="checkout-success-pay-heading">
                        <h2 id="checkout-success-pay-heading" class="ag-store-checkout-success__section-title">
                            {{ __('agricart.storefront.checkout.success_pay_next_heading') }}
                        </h2>
                        <p class="ag-store-checkout-success__section-lead">
                            {{ __('agricart.storefront.checkout.success_pay_next_lead') }}
                        </p>

                        @include('store.components.bank-transfer-accounts', [
                            'accounts' => $bankTransferAccounts ?? collect(),
                            'heading' => '',
                            'lead' => '',
                        ])
                    </section>

                    <section class="ag-store-checkout-success__proof" aria-labelledby="checkout-success-proof-heading">
                        <h2 id="checkout-success-proof-heading" class="ag-store-checkout-success__section-title">
                            {{ __('agricart.storefront.checkout.success_proof_heading') }}
                        </h2>
                        <p class="ag-store-checkout-success__section-lead">
                            {{ __('agricart.storefront.checkout.success_proof_lead') }}
                        </p>

                        @if ($canUploadHere)
                            <div class="ag-store-orders__proof-panel ag-store-orders__proof-panel--form-only">
                                <form method="post" action="{{ route('store.customer.orders.payment-proof', $orderNumber) }}" enctype="multipart/form-data" class="ag-store-checkout__form ag-store-orders__proof-form">
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
                                    <button type="submit" class="ag-store-btn ag-store-btn--primary ag-store-btn--block">
                                        {{ __('agricart.customer.orders.submit_payment_proof') }}
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('store.customer.orders.show', $orderNumber) }}" class="ag-store-checkout-success__option">
                                <span class="ag-store-checkout-success__option-title">{{ __('agricart.storefront.checkout.success_proof_upload_title') }}</span>
                                <span class="ag-store-checkout-success__option-text">{{ __('agricart.storefront.checkout.success_proof_upload_text') }}</span>
                            </a>
                        @endif
                    </section>
                </div>
            @endif

            <div class="ag-store-checkout-success__actions">
                @auth('customer')
                    <a href="{{ route('store.customer.orders.show', $orderNumber) }}" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.customer.orders.view_this_order') }}</a>
                    <a href="{{ route('store.customer.orders.show', ['order' => $orderNumber, 'print' => 1]) }}" class="ag-store-btn ag-store-btn--secondary">{{ __('agricart.customer.orders.print') }}</a>
                @endauth
                <a href="{{ route('store.home') }}" class="ag-store-btn ag-store-btn--secondary">{{ __('agricart.storefront.cart.continue_shopping') }}</a>
            </div>
        </div>
    </div>
@endsection
