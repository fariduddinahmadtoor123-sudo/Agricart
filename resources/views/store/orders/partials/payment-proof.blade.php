@php
    /** @var \App\Models\OnlineSaleOrder $order */
@endphp

<section class="ag-store-orders__detail-card ag-store-orders__payment-proof ag-store-no-print">
    <div class="ag-store-orders__proof-banner">
        <h2 class="ag-store-orders__section-title">{{ __('agricart.customer.orders.upload_receipt_heading') }}</h2>
        <p class="ag-store-orders__proof-lead">{{ __('agricart.customer.orders.upload_receipt_lead') }}</p>
    </div>

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

    <div class="ag-store-orders__proof-panel ag-store-orders__proof-panel--form-only">
        <form method="post" action="{{ route('store.customer.orders.payment-proof', $order->order_number) }}" enctype="multipart/form-data" class="ag-store-checkout__form ag-store-orders__proof-form">
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
</section>
