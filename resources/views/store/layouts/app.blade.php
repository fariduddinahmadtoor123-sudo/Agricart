<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $settings->localized('site_name') ?? config('app.name'))</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/css/store/theme.css', 'resources/js/store.js'])
</head>
<body class="ag-store">
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

    <main class="ag-store-main">
        @if (! ($onlineStoreChannel['enabled'] ?? true))
            <div class="ag-store__container ag-store-channel-banner-wrap">
                <div class="ag-store-channel-banner" role="status">
                    {{ $onlineStoreChannel['banner_message'] ?? __('sales_channels.defaults.banner_off_en') }}
                </div>
            </div>
        @endif

        @if (session('customer_auth_status') && ! request()->routeIs('store.customer.login'))
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

    @if ($settings->footer_enabled)
        @include('store.components.footer')
    @endif

    @include('store.components.whatsapp-float')

    @livewireScriptConfig
    @livewireScripts
    @stack('scripts')
</body>
</html>
