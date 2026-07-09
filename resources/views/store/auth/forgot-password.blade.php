@extends('store.layouts.app')

@section('title', __('agricart.customer.auth.forgot_password_heading') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-customer-auth-page">
        <div class="ag-store-account ag-store-account--narrow">
            <header class="ag-store-account__head">
                <h1 class="ag-store-customer-auth__title">{{ __('agricart.customer.auth.forgot_password_heading') }}</h1>
                <p class="ag-store-customer-auth__lead">{{ __('agricart.customer.auth.forgot_password_lead') }}</p>
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

            @if ($errors->any() && ! session('customer_auth_status'))
                <div class="ag-store-customer-auth__alert is-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('store.customer.password.email') }}" class="ag-store-customer-auth__form">
                @csrf
                <div class="ag-store-customer-auth__field">
                    <label for="forgot-email">{{ __('agricart.customer.fields.email') }}</label>
                    <input id="forgot-email" type="email" name="email" value="{{ old('email') }}" class="ag-store-customer-auth__input" autocomplete="email" required />
                    @error('email') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="ag-store-customer-auth__submit">{{ __('agricart.customer.auth.forgot_password_submit') }}</button>

                <p class="ag-store-customer-auth__switch">
                    <a href="{{ route('store.customer.login') }}" class="ag-store-customer-auth__switch-link">
                        {{ __('agricart.customer.auth.back_to_login') }}
                    </a>
                </p>
            </form>
        </div>
    </div>
@endsection
