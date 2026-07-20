<header class="ag-store-header">
    <div class="ag-store__container">
        <div class="ag-store-header__inner">
            <a href="{{ route('store.home') }}" class="ag-store-header__brand">
                @php
                    $brandLogoUrl = $settings->displayLogoUrl();
                    $siteName = $settings->localized('site_name') ?? config('app.name');
                @endphp
                @if ($brandLogoUrl)
                    <img src="{{ $brandLogoUrl }}" alt="{{ $siteName }}" class="ag-store-header__logo" />
                @else
                    <span class="ag-store-header__site-name ag-store-header__site-name--solo">{{ $siteName }}</span>
                @endif
            </a>

            @include('store.components.search-box', [
                'value' => request('q'),
                'placeholder' => $settings->localized('search_placeholder'),
            ])

            <div class="ag-store-header__actions">
                @include('store.components.locale-switcher', [
                    'wrapperClass' => 'ag-store-header__locale',
                ])

                @if ($settings->show_social)
                    @include('store.components.social-links', [
                        'wrapperClass' => 'ag-store-header__social',
                        'linkClass' => 'ag-store-header__social-link',
                    ])
                @endif

                @if ($settings->show_auth_links)
                    <div class="ag-store-header__auth">
                        @auth('customer')
                            @include('store.components.header-user-menu')
                        @else
                            <a href="{{ $settings->resolvedCustomerLoginUrl() }}" class="ag-store-header__auth-inline">{{ __('agricart.storefront.header.login') }}</a>
                            <span class="ag-store-header__auth-sep" aria-hidden="true">/</span>
                            <a href="{{ $settings->resolvedCustomerRegisterUrl() }}" class="ag-store-header__auth-inline">{{ __('agricart.storefront.header.register') }}</a>
                        @endauth
                    </div>
                @endif

                @if ($settings->show_cart && ($onlineStoreChannel['allow_purchase'] ?? true))
                    <a href="{{ route('store.cart') }}" class="ag-store-header__cart" title="{{ __('agricart.storefront.header.cart') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <span>{{ __('agricart.storefront.header.cart') }}</span>
                        <span class="ag-store-header__cart-count">{{ $cartItemCount ?? 0 }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
