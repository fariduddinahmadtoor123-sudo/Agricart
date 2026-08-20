<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    @php
        $siteName = $settings->localized('site_name') ?? config('app.name');
        $seoTitle = trim($__env->yieldContent('title', $siteName));
        $seoDescription = trim($__env->yieldContent('seo_description', (string) ($settings->localized('footer_tagline') ?? $siteName)));
        $canonical = trim($__env->yieldContent('canonical', request()->url()));
        $seoImage = trim($__env->yieldContent('seo_image', ''));
        if ($seoImage === '') {
            $seoImage = trim((string) ($settings->defaultOgImageUrl() ?? $logo_url ?? ''));
        }
        if ($seoImage !== '' && ! preg_match('#^https?://#i', $seoImage)) {
            $seoImage = url($seoImage);
        }
        $noindex = request()->routeIs(
            'store.search', 'store.search.*', 'store.cart', 'store.checkout', 'store.checkout.*',
            'store.customer.*', 'auth.*'
        );
        $googleVerification = trim((string) ($settings->google_site_verification ?? ''));
        $bingVerification = trim((string) ($settings->bing_site_verification ?? ''));
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $noindex ? 'noindex,follow' : 'index,follow,max-image-preview:large' }}">
    <link rel="canonical" href="{{ $canonical }}">
    @if ($googleVerification !== '')
        <meta name="google-site-verification" content="{{ $googleVerification }}">
    @endif
    @if ($bingVerification !== '')
        <meta name="msvalidate.01" content="{{ $bingVerification }}">
    @endif
    <meta property="og:locale" content="{{ app()->getLocale() === 'ur' ? 'ur_PK' : 'en_PK' }}">
    <meta property="og:type" content="@yield('seo_type', 'website')">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if ($seoImage !== '')
        <meta property="og:image" content="{{ $seoImage }}">
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif
    <meta name="twitter:card" content="{{ $seoImage !== '' ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    @stack('structured-data')
    @include('store.components.seo-tracking')
    @if (request()->routeIs('auth.register', 'auth.staff.register', 'auth.supplier.register'))
        @livewireStyles
    @endif
    @vite(['resources/css/app.css', 'resources/css/store/theme.css', 'resources/js/store.js'])
    @include('store.components.menu-hero-styles')
    @include('store.components.search-box-styles')
    @stack('styles')
</head>
<body class="ag-store">
    @include('store.components.seo-tracking-body')
    <a href="#store-main" class="ag-store-skip-link">{{ __('agricart.storefront.accessibility.skip_to_main') }}</a>
    @if ($settings->top_bar_enabled)
        @include('store.components.top-bar')
    @endif

    @if ($settings->ticker_enabled && $ticker_messages->isNotEmpty())
        @include('store.components.ticker')
    @endif

    @if ($settings->header_enabled)
        @include('store.components.header')
    @endif

    @if ($settings->menu_enabled)
        @include('store.components.menu')
    @endif

    @if (request()->routeIs('store.home') && ($settings->hero_slider_enabled ?? false))
        @php
            $heroSlides = $hero_slides ?? app(\App\Services\Store\StoreHeroSliderService::class)->activeSlides();
        @endphp
        @if ($heroSlides->isNotEmpty())
            @include('store.components.hero-slider', ['slides' => $heroSlides])
        @endif
    @endif

    <main id="store-main" class="ag-store-main" tabindex="-1">
        @if (! ($onlineStoreChannel['enabled'] ?? true))
            <div class="ag-store__container ag-store-channel-banner-wrap">
                <div class="ag-store-channel-banner" role="status">
                    {{ $onlineStoreChannel['banner_message'] ?? __('sales_channels.defaults.banner_off_en') }}
                </div>
            </div>
        @endif

        @if (session('customer_auth_status') && ! request()->routeIs('store.customer.login', 'store.customer.account'))
            <div class="ag-store__container ag-store-flash-banner">
                <div @class([
                    'ag-store-customer-auth__alert',
                    'is-danger' => session('customer_auth_status_type', 'success') === 'danger',
                    'is-success' => session('customer_auth_status_type', 'success') === 'success',
                    'is-warning' => session('customer_auth_status_type') === 'warning',
                ]) role="status">
                    {{ session('customer_auth_status') }}
                </div>
            </div>
        @endif

        @if (session('store_status') && ! request()->routeIs('store.customer.login'))
            <div class="ag-store__container ag-store-flash-banner">
                <div @class([
                    'ag-store-customer-auth__alert',
                    'is-danger' => session('store_status_type') === 'danger',
                    'is-success' => session('store_status_type', 'success') === 'success',
                    'is-warning' => session('store_status_type') === 'warning',
                ]) role="status">
                    {{ session('store_status') }}
                </div>
            </div>
        @endif
        <div class="ag-store__container">
            @yield('content')
        </div>
    </main>

    @include('store.components.brands-carousel')
    @include('store.components.testimonials-carousel')

    @if ($settings->footer_enabled)
        @include('store.components.footer')
    @endif

    @include('store.components.contact-float')

    @if (request()->routeIs('auth.register', 'auth.staff.register', 'auth.supplier.register'))
        @livewireScripts
    @endif
    @include('store.components.store-search-script')
    @include('store.components.analytics-presence-script')
    @stack('scripts')
</body>
</html>
