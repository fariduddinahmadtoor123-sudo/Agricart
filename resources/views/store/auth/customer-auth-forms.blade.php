@php
    $authTab = $authTab ?? (request()->query('tab') === 'register' || old('_form') === 'register' ? 'register' : 'login');
    $isLogin = $authTab === 'login';
    $authRedirect = request()->query('redirect');
    $redirectQuery = is_string($authRedirect) && str_starts_with($authRedirect, '/') && ! str_starts_with($authRedirect, '//')
        ? ['redirect' => $authRedirect]
        : [];
    $loginUrl = route('store.customer.login', $redirectQuery);
    $registerUrl = route('store.customer.login', array_merge(['tab' => 'register'], $redirectQuery));
    $statusMessage = session('customer_auth_status') ?? session('store_status');
    $statusType = session('customer_auth_status_type', session('store_status') ? 'warning' : 'danger');
    $provinces = config('pakistani_provinces', []);
@endphp

<div class="ag-store-customer-auth">
    <div class="ag-store-customer-auth__shell">
        <aside class="ag-store-customer-auth__brand" aria-hidden="true">
            <div class="ag-store-customer-auth__brand-inner">
                <p class="ag-store-customer-auth__eyebrow">
                    {{ $isLogin ? __('agricart.customer.auth.brand_eyebrow') : __('agricart.customer.auth.register_brand_eyebrow') }}
                </p>
                <h2 class="ag-store-customer-auth__brand-title">
                    {{ $isLogin ? __('agricart.customer.auth.brand_title') : __('agricart.customer.auth.register_brand_title') }}
                </h2>
                <p class="ag-store-customer-auth__brand-text">
                    {{ $isLogin ? __('agricart.customer.auth.brand_text') : __('agricart.customer.auth.register_brand_text') }}
                </p>
                <ul class="ag-store-customer-auth__benefits">
                    <li>{{ __('agricart.customer.auth.benefit_orders') }}</li>
                    <li>{{ __('agricart.customer.auth.benefit_checkout') }}</li>
                    <li>{{ __('agricart.customer.auth.benefit_offers') }}</li>
                </ul>
            </div>
        </aside>

        <section class="ag-store-customer-auth__panel">
            <header class="ag-store-customer-auth__panel-head">
                <h1 class="ag-store-customer-auth__title">
                    {{ $isLogin ? __('agricart.customer.auth.login_heading') : __('agricart.customer.auth.register_heading') }}
                </h1>
                <p class="ag-store-customer-auth__lead">
                    {{ $isLogin ? __('agricart.customer.auth.login_lead') : __('agricart.customer.auth.register_lead') }}
                </p>
            </header>

            @if ($statusMessage)
                <div @class([
                    'ag-store-customer-auth__alert',
                    'is-danger' => $statusType === 'danger',
                    'is-success' => $statusType === 'success',
                    'is-warning' => $statusType === 'warning',
                ]) role="alert">
                    {{ $statusMessage }}
                </div>
            @endif

            @if ($errors->any() && ! $statusMessage)
                <div class="ag-store-customer-auth__alert is-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($isLogin)
                <form method="post" action="{{ route('store.customer.login.submit') }}" class="ag-store-customer-auth__form">
                    @csrf
                    @if (filled($authRedirect) && is_string($authRedirect))
                        <input type="hidden" name="redirect" value="{{ $authRedirect }}" />
                    @endif

                    <div class="ag-store-customer-auth__field">
                        <label for="customer-login-email">{{ __('agricart.customer.fields.email') }}</label>
                        <input id="customer-login-email" type="email" name="login_email" value="{{ old('login_email') }}" class="ag-store-customer-auth__input" autocomplete="email" required />
                        @error('login_email') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-customer-auth__field">
                        <x-agricart.password-field
                            id="customer-login-password"
                            name="login_password"
                            input-class="ag-store-customer-auth__input"
                            autocomplete="current-password"
                            :label="__('agricart.customer.fields.password')"
                            required
                        />
                        @error('login_password') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                    </div>

                    <label class="ag-store-customer-auth__checkbox">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) />
                        <span>{{ __('agricart.customer.auth.remember_me') }}</span>
                    </label>

                    <p class="ag-store-customer-auth__forgot">
                        <a href="{{ route('store.customer.password.request') }}" class="ag-store-customer-auth__switch-link">
                            {{ __('agricart.customer.auth.forgot_password_link') }}
                        </a>
                    </p>

                    <button type="submit" class="ag-store-customer-auth__submit">
                        {{ __('agricart.customer.auth.login_submit') }}
                    </button>

                    <p class="ag-store-customer-auth__switch">
                        {{ __('agricart.customer.auth.no_account') }}
                        <a href="{{ $registerUrl }}" class="ag-store-customer-auth__switch-link">
                            {{ __('agricart.customer.auth.create_account') }}
                        </a>
                    </p>
                </form>
            @else
                <form method="post" action="{{ route('store.customer.register.submit') }}" class="ag-store-customer-auth__form">
                    @csrf
                    <input type="hidden" name="_form" value="register" />

                    <div class="ag-store-customer-auth__section">
                        <h2 class="ag-store-customer-auth__section-title">{{ __('agricart.customer.auth.section_account') }}</h2>
                        <div class="ag-store-customer-auth__grid ag-store-customer-auth__grid--2">
                            <div class="ag-store-customer-auth__field">
                                <label for="customer-first-name">{{ __('agricart.customer.fields.first_name') }} *</label>
                                <input id="customer-first-name" type="text" name="first_name" value="{{ old('first_name') }}" class="ag-store-customer-auth__input" autocomplete="given-name" required />
                                @error('first_name') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <label for="customer-last-name">{{ __('agricart.customer.fields.last_name') }} *</label>
                                <input id="customer-last-name" type="text" name="last_name" value="{{ old('last_name') }}" class="ag-store-customer-auth__input" autocomplete="family-name" required />
                                @error('last_name') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <label for="customer-email">{{ __('agricart.customer.fields.email') }} *</label>
                                <input id="customer-email" type="email" name="email" value="{{ old('email') }}" class="ag-store-customer-auth__input" autocomplete="email" required />
                                @error('email') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <label for="customer-phone">{{ __('agricart.customer.fields.phone') }} *</label>
                                <input id="customer-phone" type="tel" name="phone" value="{{ old('phone') }}" class="ag-store-customer-auth__input" autocomplete="tel" required />
                                @error('phone') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <x-agricart.password-field
                                    id="customer-password"
                                    name="password"
                                    input-class="ag-store-customer-auth__input"
                                    autocomplete="new-password"
                                    :label="__('agricart.customer.fields.password') . ' *'"
                                    required
                                />
                                @error('password') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <x-agricart.password-field
                                    id="customer-password-confirm"
                                    name="password_confirmation"
                                    input-class="ag-store-customer-auth__input"
                                    autocomplete="new-password"
                                    :label="__('agricart.customer.fields.password_confirm') . ' *'"
                                    required
                                />
                                @error('password_confirmation') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="ag-store-customer-auth__section">
                        <h2 class="ag-store-customer-auth__section-title">{{ __('agricart.customer.auth.section_address') }}</h2>
                        <div class="ag-store-customer-auth__grid">
                            <div class="ag-store-customer-auth__field ag-store-customer-auth__field--full">
                                <label for="customer-address1">{{ __('agricart.customer.fields.address_line1') }} *</label>
                                <input id="customer-address1" type="text" name="address_line1" value="{{ old('address_line1') }}" class="ag-store-customer-auth__input" autocomplete="address-line1" required />
                                @error('address_line1') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field ag-store-customer-auth__field--full">
                                <label for="customer-address2">{{ __('agricart.customer.fields.address_line2') }}</label>
                                <input id="customer-address2" type="text" name="address_line2" value="{{ old('address_line2') }}" class="ag-store-customer-auth__input" autocomplete="address-line2" />
                                @error('address_line2') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <label for="customer-city">{{ __('agricart.customer.fields.city') }} *</label>
                                <input id="customer-city" type="text" name="city" value="{{ old('city') }}" class="ag-store-customer-auth__input" autocomplete="address-level2" required />
                                @error('city') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <label for="customer-state">{{ __('agricart.customer.fields.state') }} *</label>
                                <select id="customer-state" name="state" class="ag-store-customer-auth__input" required>
                                    <option value="">{{ __('agricart.customer.hints.select_province') }}</option>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province }}" @selected(old('state') === $province)>{{ $province }}</option>
                                    @endforeach
                                </select>
                                @error('state') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <label for="customer-postal">{{ __('agricart.customer.fields.postal_code') }}</label>
                                <input id="customer-postal" type="text" name="postal_code" value="{{ old('postal_code') }}" class="ag-store-customer-auth__input" autocomplete="postal-code" />
                                @error('postal_code') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ag-store-customer-auth__field">
                                <label for="customer-country">{{ __('agricart.customer.fields.country') }}</label>
                                <input id="customer-country" type="text" name="country" value="{{ old('country', 'Pakistan') }}" class="ag-store-customer-auth__input" autocomplete="country-name" />
                                @error('country') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <label class="ag-store-customer-auth__checkbox">
                        <input type="checkbox" name="accepts_marketing" value="1" @checked(old('accepts_marketing')) />
                        <span>{{ __('agricart.customer.auth.marketing_opt_in') }}</span>
                    </label>

                    <label class="ag-store-customer-auth__checkbox">
                        <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted')) required />
                        <span>{{ __('agricart.customer.auth.terms_label') }}</span>
                    </label>
                    @error('terms_accepted') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror

                    <button type="submit" class="ag-store-customer-auth__submit">
                        {{ __('agricart.customer.auth.register_submit') }}
                    </button>

                    <p class="ag-store-customer-auth__switch">
                        {{ __('agricart.customer.auth.have_account') }}
                        <a href="{{ $loginUrl }}" class="ag-store-customer-auth__switch-link">
                            {{ __('agricart.customer.auth.sign_in') }}
                        </a>
                    </p>
                </form>
            @endif
        </section>
    </div>
</div>
