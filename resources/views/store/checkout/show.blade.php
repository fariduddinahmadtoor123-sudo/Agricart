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
        <form
            method="post"
            action="{{ route('store.checkout.place') }}"
            class="ag-store-checkout__layout"
            data-checkout-form
            x-data="agricartCheckoutDelivery(@js([
                'methods' => $deliveryOptions,
                'selectedId' => (int) old('delivery_method_id', $deliveryOptions[0]['id'] ?? 0),
                'selectedPayment' => (string) old('customer_payment_method', 'bank_transfer'),
                'codAdvanceHint' => __('agricart.storefront.checkout.cod_advance_hint', ['percent' => ':percent']),
                'fullAdvanceHint' => __('agricart.storefront.checkout.full_advance_hint'),
                'initialTab' => $errors->has('customer_notes') ? 'notes' : 'address',
            ]))"
        >
            @csrf
            <input type="hidden" name="checkout_token" value="{{ old('checkout_token', $checkoutToken) }}" />
            <input type="hidden" name="customer_payment_method" x-model="selectedPayment" />

            <div class="ag-store-checkout__main">

                <p class="ag-store-checkout__account-line">
                    <span class="ag-store-checkout__account-label">{{ __('agricart.storefront.checkout.customer') }}</span>
                    <strong>{{ $customer->displayLabel() }}</strong>
                    @if (filled($customer->email))
                        <span class="ag-store-checkout__account-meta">{{ $customer->email }}</span>
                    @endif
                </p>

                <section class="ag-store-checkout__step ag-store-checkout__step--delivery" aria-labelledby="checkout-step-delivery-method">
                    <div class="ag-store-checkout__step-head">
                        <span class="ag-store-checkout__step-badge" aria-hidden="true">1</span>
                        <h2 id="checkout-step-delivery-method" class="ag-store-checkout__step-title">{{ __('agricart.storefront.checkout.delivery_method') }}</h2>
                    </div>
                    <div class="ag-store-checkout__step-body">
                        <template x-if="methods.length === 0">
                            <p class="ag-store-checkout__delivery-empty">{{ $deliveryOptionsEmptyReason ?? __('agricart.storefront.checkout.no_delivery_methods') }}</p>
                        </template>

                        <div class="ag-store-checkout__delivery-grid" role="radiogroup" aria-label="{{ __('agricart.storefront.checkout.delivery_method') }}" x-show="methods.length > 0">
                            <template x-for="method in methods" :key="method.id">
                                <label class="ag-store-checkout__delivery-option">
                                    <input
                                        type="radio"
                                        name="delivery_method_id"
                                        class="ag-store-checkout__pay-input"
                                        :value="method.id"
                                        x-model.number="selectedId"
                                        required
                                    />
                                    <span class="ag-store-checkout__delivery-card">
                                        <span class="ag-store-checkout__delivery-name" x-text="method.name"></span>
                                        <span class="ag-store-checkout__delivery-desc" x-show="method.description" x-text="method.description"></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                        @error('delivery_method_id') <span class="ag-store-checkout__error">{{ $message }}</span> @enderror
                        @error('customer_payment_method') <span class="ag-store-checkout__error">{{ $message }}</span> @enderror

                        <p class="ag-store-checkout__pay-mode" role="note">
                            <span class="ag-store-checkout__pay-mode-label">{{ __('agricart.storefront.checkout.payment_method_short') }}</span>
                            <strong>{{ __('pos.payment_methods.bank_transfer') }}</strong>
                            <span class="ag-store-checkout__pay-mode-hint">{{ __('agricart.storefront.checkout.payment_bank_only_hint') }}</span>
                        </p>
                    </div>
                </section>

                <section class="ag-store-checkout__step ag-store-checkout__step--address ag-store-checkout__step--compact" aria-labelledby="checkout-step-address">
                    <div class="ag-store-checkout__step-head">
                        <span class="ag-store-checkout__step-badge" aria-hidden="true">2</span>
                        <h2 id="checkout-step-address" class="ag-store-checkout__step-title">{{ __('agricart.storefront.checkout.shipping_heading') }}</h2>
                    </div>
                    <div class="ag-store-checkout__step-body ag-store-checkout__step-body--compact">
                        <div class="ag-store-pdp__tabs ag-store-checkout__tabs">
                            <div class="ag-store-pdp__tablist" role="tablist" aria-label="{{ __('agricart.storefront.checkout.secondary_details') }}">
                                <button
                                    type="button"
                                    class="ag-store-pdp__tab"
                                    role="tab"
                                    id="checkout-tab-address"
                                    aria-controls="checkout-panel-address"
                                    data-tab="address"
                                    :aria-selected="detailsTab === 'address' ? 'true' : 'false'"
                                    :class="{ 'is-active': detailsTab === 'address' }"
                                    @click="detailsTab = 'address'"
                                >{{ __('agricart.storefront.checkout.tabs.address') }}</button>
                                <button
                                    type="button"
                                    class="ag-store-pdp__tab"
                                    role="tab"
                                    id="checkout-tab-notes"
                                    aria-controls="checkout-panel-notes"
                                    data-tab="notes"
                                    :aria-selected="detailsTab === 'notes' ? 'true' : 'false'"
                                    :class="{ 'is-active': detailsTab === 'notes' }"
                                    @click="detailsTab = 'notes'"
                                >{{ __('agricart.storefront.checkout.tabs.notes') }}</button>
                            </div>

                            <div
                                class="ag-store-pdp__panel"
                                role="tabpanel"
                                id="checkout-panel-address"
                                aria-labelledby="checkout-tab-address"
                                data-panel="address"
                                :class="{ 'is-active': detailsTab === 'address' }"
                                x-show="detailsTab === 'address'"
                            >
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

                            <div
                                class="ag-store-pdp__panel"
                                role="tabpanel"
                                id="checkout-panel-notes"
                                aria-labelledby="checkout-tab-notes"
                                data-panel="notes"
                                :class="{ 'is-active': detailsTab === 'notes' }"
                                x-show="detailsTab === 'notes'"
                            >
                                <label class="ag-store-checkout__field ag-store-checkout__field--flush">
                                    <span class="ag-store-checkout__label">{{ __('agricart.storefront.checkout.order_notes') }}</span>
                                    <span class="ag-store-checkout__label ag-store-checkout__label--muted">{{ __('agricart.storefront.checkout.order_notes_hint') }}</span>
                                    <textarea
                                        name="customer_notes"
                                        class="ag-store-checkout__textarea ag-store-checkout__textarea--compact"
                                        rows="3"
                                        placeholder="{{ __('agricart.storefront.checkout.order_notes_placeholder') }}"
                                    >{{ old('customer_notes') }}</textarea>
                                    @error('customer_notes') <span class="ag-store-checkout__error">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
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
                                            <img src="{{ $thumbUrl }}" alt="{{ $productService->localizedName($p) }}" class="ag-store-checkout__thumb-img" loading="lazy" decoding="async" width="56" height="56" />
                                        @else
                                            <span class="ag-store-checkout__thumb-placeholder">{{ mb_strtoupper(mb_substr($productService->localizedName($p), 0, 1)) }}</span>
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

                <div class="ag-store-checkout__aside-actions">
                    <div class="ag-store-checkout__notice" role="note">
                        <p>{{ __('agricart.storefront.checkout.notice') }}</p>
                    </div>

                    <div
                        class="ag-store-checkout__delivery-meta ag-store-checkout__delivery-meta--submit"
                        x-show="selectedMethod && (selectedMethod?.notice || advanceHint)"
                        x-cloak
                    >
                        <p class="ag-store-checkout__delivery-notice" x-show="selectedMethod?.notice" x-text="selectedMethod?.notice"></p>
                        <p class="ag-store-checkout__delivery-hint" x-show="advanceHint" x-text="advanceHint"></p>
                    </div>

                    <button type="submit" class="ag-store-btn ag-store-btn--primary ag-store-btn--block ag-store-checkout__submit" @disabled(empty($deliveryOptions))>
                        {{ __('agricart.storefront.checkout.place_order') }}
                    </button>
                </div>
            </aside>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('agricartCheckoutDelivery', (config) => ({
            methods: config.methods || [],
            selectedId: config.selectedId || 0,
            selectedPayment: config.selectedPayment || 'bank_transfer',
            detailsTab: config.initialTab || 'address',
            codAdvanceHint: config.codAdvanceHint || '',
            fullAdvanceHint: config.fullAdvanceHint || '',
            init() {
                this.$watch('selectedId', () => this.ensurePayment());
                this.ensurePayment();
            },
            get selectedMethod() {
                return this.methods.find((method) => Number(method.id) === Number(this.selectedId)) || null;
            },
            get allowedPayments() {
                return this.selectedMethod?.payments || [];
            },
            get advanceHint() {
                if (! this.selectedMethod) {
                    return '';
                }
                if (this.selectedMethod.full_advance_required) {
                    return this.fullAdvanceHint;
                }
                const percent = Number(this.selectedMethod.cod_min_advance_percent || 0);
                if (percent > 0) {
                    return this.codAdvanceHint.replace(':percent', String(percent));
                }
                return '';
            },
            ensurePayment() {
                const allowed = this.allowedPayments;
                if (allowed.includes('bank_transfer')) {
                    this.selectedPayment = 'bank_transfer';
                    return;
                }
                this.selectedPayment = allowed[0] || '';
            },
        }));
    });
</script>
@endpush
