@extends('store.layouts.app')

@section('title', __('agricart.customer.auth.profile_heading') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-customer-auth-page">
        <div class="ag-store-account">
            <header class="ag-store-account__head">
                <h1 class="ag-store-customer-auth__title">{{ __('agricart.customer.auth.profile_heading') }}</h1>
                <p class="ag-store-customer-auth__lead">{{ __('agricart.customer.auth.profile_lead') }}</p>
                <p class="ag-store-account__nav">
                    <a href="{{ route('store.customer.orders') }}" class="ag-store-orders__link">{{ __('agricart.customer.auth.my_orders') }}</a>
                    <a href="{{ route('store.customer.inbox') }}" class="ag-store-orders__link">{{ __('agricart.customer.auth.agricart_inbox') }}</a>
                </p>
            </header>

            @if (session('customer_auth_status'))
                <div @class([
                    'ag-store-customer-auth__alert',
                    'is-danger' => session('customer_auth_status_type') === 'danger',
                    'is-success' => session('customer_auth_status_type', 'success') === 'success',
                    'is-warning' => session('customer_auth_status_type') === 'warning',
                ]) role="alert">
                    {{ session('customer_auth_status') }}
                </div>
            @endif

            <form method="post" action="{{ route('store.customer.account.profile') }}" class="ag-store-customer-auth__form ag-store-account__section">
                @csrf
                <h2 class="ag-store-customer-auth__section-title">{{ __('agricart.customer.auth.section_account') }}</h2>

                <div class="ag-store-customer-auth__grid ag-store-customer-auth__grid--2">
                    <div class="ag-store-customer-auth__field">
                        <label for="account-first-name">{{ __('agricart.customer.fields.first_name') }}</label>
                        <input id="account-first-name" type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="ag-store-customer-auth__input" required />
                        @error('first_name') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ag-store-customer-auth__field">
                        <label for="account-last-name">{{ __('agricart.customer.fields.last_name') }}</label>
                        <input id="account-last-name" type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="ag-store-customer-auth__input" required />
                        @error('last_name') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="ag-store-customer-auth__field">
                    <label for="account-email">{{ __('agricart.customer.fields.email') }}</label>
                    <input id="account-email" type="email" name="email" value="{{ old('email', $customer->email) }}" class="ag-store-customer-auth__input" autocomplete="email" required />
                    @error('email') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                </div>

                <div class="ag-store-customer-auth__field">
                    <label for="account-phone">{{ __('agricart.customer.fields.phone') }}</label>
                    <input id="account-phone" type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" class="ag-store-customer-auth__input" autocomplete="tel" required />
                    @error('phone') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="ag-store-customer-auth__submit">{{ __('agricart.customer.auth.profile_save') }}</button>
            </form>

            <form method="post" action="{{ route('store.customer.account.address') }}" class="ag-store-customer-auth__form ag-store-account__section">
                @csrf
                <h2 class="ag-store-customer-auth__section-title">{{ __('agricart.customer.auth.section_address') }}</h2>

                @include('store.components.customer-address-fields', [
                    'customer' => $customer,
                    'idPrefix' => 'account',
                    'provinces' => config('pakistani_provinces', []),
                ])

                <button type="submit" class="ag-store-customer-auth__submit">{{ __('agricart.customer.auth.address_save') }}</button>
            </form>

            <form method="post" action="{{ route('store.customer.account.password') }}" class="ag-store-customer-auth__form ag-store-account__section">
                @csrf
                <h2 class="ag-store-customer-auth__section-title">{{ __('agricart.customer.auth.password_section') }}</h2>

                <div class="ag-store-customer-auth__field">
                    <x-agricart.password-field
                        id="account-current-password"
                        name="current_password"
                        input-class="ag-store-customer-auth__input"
                        :label="__('agricart.customer.auth.current_password')"
                        autocomplete="current-password"
                        required
                    />
                    @error('current_password') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                </div>

                <div class="ag-store-customer-auth__grid ag-store-customer-auth__grid--2">
                    <div class="ag-store-customer-auth__field">
                        <x-agricart.password-field
                            id="account-new-password"
                            name="password"
                            input-class="ag-store-customer-auth__input"
                            :label="__('agricart.customer.fields.password')"
                            autocomplete="new-password"
                            required
                        />
                        @error('password') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ag-store-customer-auth__field">
                        <x-agricart.password-field
                            id="account-password-confirm"
                            name="password_confirmation"
                            input-class="ag-store-customer-auth__input"
                            :label="__('agricart.customer.fields.password_confirm')"
                            autocomplete="new-password"
                            required
                        />
                    </div>
                </div>

                <button type="submit" class="ag-store-customer-auth__submit">{{ __('agricart.customer.auth.password_save') }}</button>
            </form>
        </div>
    </div>
@endsection
