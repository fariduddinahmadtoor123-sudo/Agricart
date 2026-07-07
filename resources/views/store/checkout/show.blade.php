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
        <section class="ag-store-checkout__summary">
            <h2 class="ag-store-section-title">{{ __('agricart.storefront.checkout.order_summary') }}</h2>
            <ul class="ag-store-checkout__lines">
                @foreach ($lines as $line)
                    <li>
                        <span>{{ app(\App\Services\Store\StoreProductService::class)->localizedName($line['product']) }} × {{ $line['quantity'] }}</span>
                        <span>{{ __('agricart.storefront.products.price', ['amount' => $line['line_total_formatted']]) }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="ag-store-checkout__subtotal">
                <span>{{ __('agricart.storefront.cart.subtotal') }}</span>
                <strong>{{ __('agricart.storefront.products.price', ['amount' => $subtotal]) }}</strong>
            </p>
        </section>

        <section class="ag-store-checkout__form-wrap">
            <h2 class="ag-store-section-title">{{ __('agricart.storefront.checkout.delivery_payment') }}</h2>
            <p class="ag-store-checkout__customer">
                <strong>{{ $customer->displayLabel() }}</strong>
                @if (filled($customer->primaryPhone()))
                    <span> · {{ $customer->primaryPhone() }}</span>
                @endif
            </p>

            <form method="post" action="{{ route('store.checkout.place') }}" class="ag-store-checkout__form">
                @csrf

                <fieldset class="ag-store-checkout__field">
                    <legend class="ag-store-checkout__legend">{{ __('agricart.storefront.checkout.payment_method') }}</legend>
                    @foreach ($paymentMethods as $value => $label)
                        <label class="ag-store-checkout__radio">
                            <input type="radio" name="customer_payment_method" value="{{ $value }}" @checked(old('customer_payment_method', 'bank_transfer') === $value) required />
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </fieldset>

                <label class="ag-store-checkout__field">
                    <span class="ag-store-checkout__label">{{ __('agricart.storefront.checkout.payment_note') }}</span>
                    <input type="text" name="customer_payment_note" class="ag-store-checkout__input" value="{{ old('customer_payment_note') }}" placeholder="{{ __('agricart.storefront.checkout.payment_note_hint') }}" />
                </label>

                <label class="ag-store-checkout__field">
                    <span class="ag-store-checkout__label">{{ __('agricart.storefront.checkout.order_notes') }}</span>
                    <textarea name="customer_notes" class="ag-store-checkout__textarea" rows="3" placeholder="{{ __('agricart.storefront.checkout.order_notes_hint') }}">{{ old('customer_notes') }}</textarea>
                </label>

                <p class="ag-store-checkout__notice">{{ __('agricart.storefront.checkout.notice') }}</p>

                <button type="submit" class="ag-store-btn ag-store-btn--primary ag-store-btn--block">
                    {{ __('agricart.storefront.checkout.place_order') }}
                </button>
            </form>
        </section>
    </div>
@endsection
