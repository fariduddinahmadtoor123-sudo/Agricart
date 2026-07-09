@extends('store.layouts.app')

@section('title', __('agricart.customer.auth.reset_password_heading') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-customer-auth-page">
        <div class="ag-store-account ag-store-account--narrow">
            <header class="ag-store-account__head">
                <h1 class="ag-store-customer-auth__title">{{ __('agricart.customer.auth.reset_password_heading') }}</h1>
                <p class="ag-store-customer-auth__lead">{{ __('agricart.customer.auth.reset_password_lead') }}</p>
            </header>

            @if ($errors->any())
                <div class="ag-store-customer-auth__alert is-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('store.customer.password.update') }}" class="ag-store-customer-auth__form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}" />

                <div class="ag-store-customer-auth__field">
                    <label for="reset-email">{{ __('agricart.customer.fields.email') }}</label>
                    <input id="reset-email" type="email" name="email" value="{{ old('email', $email) }}" class="ag-store-customer-auth__input" autocomplete="email" required />
                    @error('email') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                </div>

                <div class="ag-store-customer-auth__grid ag-store-customer-auth__grid--2">
                    <div class="ag-store-customer-auth__field">
                        <x-agricart.password-field
                            id="reset-password"
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
                            id="reset-password-confirm"
                            name="password_confirmation"
                            input-class="ag-store-customer-auth__input"
                            :label="__('agricart.customer.fields.password_confirm')"
                            autocomplete="new-password"
                            required
                        />
                    </div>
                </div>

                <button type="submit" class="ag-store-customer-auth__submit">{{ __('agricart.customer.auth.reset_password_submit') }}</button>
            </form>
        </div>
    </div>
@endsection
