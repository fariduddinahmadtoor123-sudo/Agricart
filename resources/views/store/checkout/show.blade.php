@extends('store.layouts.app')

@section('title', __('agricart.storefront.checkout.title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <h1 class="ag-store-page-title">{{ __('agricart.storefront.checkout.title') }}</h1>

    @if ($errors->any())
        <div class="ag-store-alert ag-store-alert--error" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="ag-store-checkout">
        <div class="ag-store-checkout__customer-card ag-store-checkout__customer-card--top">
            <span class="ag-store-checkout__customer-label">{{ __('agricart.storefront.checkout.customer') }}</span>
            <p class="ag-store-checkout__customer">
                <strong>{{ $customer->displayLabel() }}</strong>
                @if (filled($customer->email))
                    <span class="ag-store-checkout__customer-phone">{{ $customer->email }}</span>
                @endif
            </p>
        </div>

        <form method="post" action="{{ route('store.checkout.place') }}" class="ag-store-checkout__layout" enctype="multipart/form-data">
            @csrf

            <div class="ag-store-checkout__main">
                <section class="ag-store-checkout__panel ag-store-checkout__panel--delivery" aria-labelledby="checkout-step-delivery">
                    <header class="ag-store-checkout__panel-head">
                        <span class="ag-store-checkout__step-badge" aria-hidden="true">1</span>
                        <h2 id="checkout-step-delivery" class="ag-store-checkout__panel-title">{{ __('agricart.storefront.checkout.shipping_heading') }}</h2>
                    </header>
                    <div class="ag-store-checkout__panel-body">
                        <div class="ag-store-checkout__field-row">
                            <label class="ag-store-checkout__field">
                                <span class="ag-store-checkout__label">{{ __('agricart.storefront.checkout.ship_contact_name') }}</span>
                                <input
                                    type="text"
                                    name="ship_contact_name"
                                    class="ag-store-checkout__input"
                                    value="{{ old('ship_contact_name', $customer->displayLabel()) }}"
                                    autocomplete="name"
                                    required
                                />
                                @error('ship_contact_name') <span class="ag-store-checkout__error">{{ $message }}</span> @enderror
                            </label>

                            <label class="ag-store-checkout__field">
                                <span class="ag-store-checkout__label">{{ __('agricart.customer.fields.phone') }}</span>
                                <input
                                    type="tel"
                                    name="ship_phone"
                                    class="ag-store-checkout__input"
                                    value="{{ old('ship_phone', $customer->primaryPhone()) }}"
                                    autocomplete="tel"
                                    required
                                />
                                @error('ship_phone') <span class="ag-store-checkout__error">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        @include('store.components.customer-address-fields', [
                            'customer' => $customer,
                            'idPrefix' => 'checkout',
                            'inputClass' => 'ag-store-checkout__input',
                            'gridClass' => 'ag-store-checkout__address-grid',
                            'provinces' => $provinces,
                        ])

                        <label class="ag-store-checkout__checkbox">
                            <input type="checkbox" name="save_address_to_profile" value="1" @checked(old('save_address_to_profile', true)) />
                            <span>{{ __('agricart.storefront.checkout.save_address_to_profile') }}</span>
                        </label>
                    </div>
                </section>

                <div class="ag-store-checkout__completion">
                    <section class="ag-store-checkout__panel ag-store-checkout__panel--notes" aria-labelledby="checkout-step-notes">
                        <header class="ag-store-checkout__panel-head ag-store-checkout__panel-head--compact">
                            <span class="ag-store-checkout__step-badge" aria-hidden="true">3</span>
                            <h2 id="checkout-step-notes" class="ag-store-checkout__panel-title">{{ __('agricart.storefront.checkout.order_notes') }}</h2>
                        </header>
                        <div class="ag-store-checkout__panel-body ag-store-checkout__panel-body--tight">
                            <label class="ag-store-checkout__field ag-store-checkout__field--flush">
                                <span class="ag-store-checkout__label ag-store-checkout__label--muted">{{ __('agricart.storefront.checkout.order_notes_hint') }}</span>
                                <textarea
                                    name="customer_notes"
                                    class="ag-store-checkout__textarea ag-store-checkout__textarea--compact"
                                    rows="2"
                                    placeholder="{{ __('agricart.storefront.checkout.order_notes_placeholder') }}"
                                >{{ old('customer_notes') }}</textarea>
                            </label>
                        </div>
                    </section>

                    <div class="ag-store-checkout__notice" role="note">
                        <p>{{ __('agricart.storefront.checkout.notice') }}</p>
                    </div>

                    <button type="submit" class="ag-store-btn ag-store-btn--primary ag-store-btn--block ag-store-checkout__submit">
                        {{ __('agricart.storefront.checkout.place_order') }}
                    </button>
                </div>
            </div>

            <aside class="ag-store-checkout__aside" aria-label="{{ __('agricart.storefront.checkout.order_summary') }}">
                <section class="ag-store-checkout__card ag-store-checkout__summary" aria-labelledby="checkout-order-summary">
                    <h2 id="checkout-order-summary" class="ag-store-checkout__card-title">{{ __('agricart.storefront.checkout.order_summary') }}</h2>
                    <ul class="ag-store-checkout__items ag-store-checkout__items--scroll">
                        @foreach ($lines as $index => $line)
                            @php
                                $p = $line['product'];
                                $thumbUrl = $productService->imageUrl($p);
                            @endphp
                            <li class="ag-store-checkout__item">
                                <span class="ag-store-checkout__item-sn" aria-hidden="true">{{ $index + 1 }}</span>
                                <div class="ag-store-checkout__item-thumb">
                                    <a href="{{ route('store.product', $p->slug) }}" class="ag-store-checkout__thumb-link">
                                        @if ($thumbUrl)
                                            <img src="{{ $thumbUrl }}" alt="" class="ag-store-checkout__thumb-img" loading="lazy" width="56" height="56" />
                                        @else
                                            <span class="ag-store-checkout__thumb-placeholder">{{ strtoupper(substr($productService->localizedName($p), 0, 1)) }}</span>
                                        @endif
                                    </a>
                                </div>
                                <div class="ag-store-checkout__item-body">
                                    <a href="{{ route('store.product', $p->slug) }}" class="ag-store-checkout__item-name">{{ $productService->localizedName($p) }}</a>
                                    <p class="ag-store-checkout__item-meta">
                                        <span>{{ __('agricart.storefront.checkout.qty_line', ['qty' => $line['quantity']]) }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span>{{ __('agricart.storefront.products.price', ['amount' => $line['unit_price_formatted']]) }} {{ __('agricart.storefront.checkout.each') }}</span>
                                    </p>
                                </div>
                                <div class="ag-store-checkout__item-total">
                                    {{ __('agricart.storefront.products.price', ['amount' => $line['line_total_formatted']]) }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="ag-store-checkout__subtotal">
                        <span>{{ __('agricart.storefront.cart.subtotal') }}</span>
                        <strong>{{ __('agricart.storefront.products.price', ['amount' => $subtotal]) }}</strong>
                    </p>
                </section>

                <section class="ag-store-checkout__step ag-store-checkout__step--payment" aria-labelledby="checkout-step-payment">
                    <div class="ag-store-checkout__step-head">
                        <span class="ag-store-checkout__step-badge" aria-hidden="true">2</span>
                        <h3 id="checkout-step-payment" class="ag-store-checkout__step-title">{{ __('agricart.storefront.checkout.payment_method') }}</h3>
                    </div>
                    <div class="ag-store-checkout__step-body">
                        <div class="ag-store-checkout__pay-grid" role="radiogroup" aria-label="{{ __('agricart.storefront.checkout.payment_method') }}">
                            @foreach ($paymentMethods as $value => $label)
                                <label @class(['ag-store-checkout__pay-option', 'is-cash' => $value === 'cash', 'is-bank' => $value === 'bank_transfer'])>
                                    <input
                                        type="radio"
                                        name="customer_payment_method"
                                        value="{{ $value }}"
                                        class="ag-store-checkout__pay-input"
                                        @checked(old('customer_payment_method', 'bank_transfer') === $value)
                                        required
                                    />
                                    <span class="ag-store-checkout__pay-card">
                                        <span class="ag-store-checkout__pay-icon" aria-hidden="true">
                                            @if ($value === 'cash')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                                            @endif
                                        </span>
                                        <span class="ag-store-checkout__pay-label">{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="ag-store-checkout__cash-note">
                            <p>{{ __('agricart.storefront.checkout.cash_payment_lead') }}</p>
                        </div>

                        <div class="ag-store-checkout__bank-panel">
                            <p class="ag-store-checkout__bank-panel-lead">{{ __('agricart.storefront.checkout.bank_payment_lead') }}</p>

                            <label class="ag-store-checkout__field">
                                <span class="ag-store-checkout__label">{{ __('agricart.storefront.checkout.payment_reference') }}</span>
                                <input
                                    type="text"
                                    name="customer_payment_reference"
                                    class="ag-store-checkout__input"
                                    value="{{ old('customer_payment_reference') }}"
                                    placeholder="{{ __('agricart.storefront.checkout.payment_reference_hint') }}"
                                    autocomplete="off"
                                />
                                @error('customer_payment_reference') <span class="ag-store-checkout__error">{{ $message }}</span> @enderror
                            </label>

                            <label class="ag-store-checkout__field">
                                <span class="ag-store-checkout__label">{{ __('agricart.storefront.checkout.bank_receipt_file') }}</span>
                                <input
                                    type="file"
                                    name="bank_receipt"
                                    class="ag-store-checkout__input ag-store-checkout__file"
                                    accept="image/jpeg,image/png,image/webp,application/pdf"
                                />
                                <span class="ag-store-checkout__field-hint">{{ __('agricart.storefront.checkout.bank_receipt_hint') }}</span>
                                @error('bank_receipt') <span class="ag-store-checkout__error">{{ $message }}</span> @enderror
                            </label>

                            <label class="ag-store-checkout__field">
                                <span class="ag-store-checkout__label">{{ __('agricart.storefront.checkout.payment_note') }}</span>
                                <input
                                    type="text"
                                    name="customer_payment_note"
                                    class="ag-store-checkout__input"
                                    value="{{ old('customer_payment_note') }}"
                                    placeholder="{{ __('agricart.storefront.checkout.payment_note_hint') }}"
                                    autocomplete="off"
                                />
                            </label>
                        </div>
                    </div>
                </section>
            </aside>
        </form>
    </div>
@endsection
