<header class="ag-store-header">
    <div class="ag-store__container">
        <div class="ag-store-header__inner">
            <a href="{{ route('store.home') }}" class="ag-store-header__brand">
                @if ($logo_url)
                    <img src="{{ $logo_url }}" alt="" class="ag-store-header__logo" />
                @else
                    <span class="ag-store-header__logo-fallback">{{ strtoupper(substr($settings->localized('site_name') ?? 'A', 0, 1)) }}</span>
                @endif
                <span class="ag-store-header__site-name">{{ $settings->localized('site_name') }}</span>
            </a>

            <div class="ag-store-header__search">
                <form action="{{ route('store.search') }}" method="get" class="ag-store-header__search-form" role="search">
                    <input
                        type="search"
                        name="q"
                        class="ag-store-header__search-input"
                        placeholder="{{ $settings->localized('search_placeholder') }}"
                        value="{{ request('q') }}"
                    />
                    <button type="submit" class="ag-store-header__search-btn">{{ __('agricart.storefront.header.search') }}</button>
                </form>
            </div>

            <div class="ag-store-header__actions">
                @if ($settings->show_social)
                    @include('store.components.social-links', [
                        'wrapperClass' => 'ag-store-header__social',
                        'linkClass' => 'ag-store-header__social-link',
                    ])
                @endif

                <div class="ag-store-header__locale">
                    <a href="{{ route('agricart.locale', ['locale' => 'en', 'redirect' => request()->getRequestUri()]) }}" @class(['ag-store-header__locale-link', 'is-active' => app()->getLocale() === 'en'])>EN</a>
                    <a href="{{ route('agricart.locale', ['locale' => 'ur', 'redirect' => request()->getRequestUri()]) }}" @class(['ag-store-header__locale-link', 'is-active' => app()->getLocale() === 'ur'])>UR</a>
                </div>

                @if ($settings->show_auth_links)
                    <div class="ag-store-header__auth">
                        @auth('customer')
                            <span class="ag-store-header__auth-user" title="{{ auth('customer')->user()->email }}">
                                {{ auth('customer')->user()->displayLabel() }}
                            </span>
                            <form method="post" action="{{ route('store.customer.logout') }}" class="ag-store-header__logout-form">
                                @csrf
                                <button type="submit" class="ag-store-header__auth-link">{{ __('agricart.customer.auth.logout') }}</button>
                            </form>
                        @else
                            <a href="{{ $settings->resolvedCustomerLoginUrl() }}" class="ag-store-header__auth-link">{{ __('agricart.storefront.header.login') }}</a>
                            <a href="{{ $settings->resolvedCustomerRegisterUrl() }}" class="ag-store-header__auth-link ag-store-header__auth-link--primary">{{ __('agricart.storefront.header.register') }}</a>
                        @endauth
                    </div>
                @endif

                @if ($settings->show_cart)
                    <button type="button" class="ag-store-header__cart" title="{{ __('agricart.storefront.header.cart') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <span>{{ __('agricart.storefront.header.cart') }}</span>
                        <span class="ag-store-header__cart-count">0</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</header>
