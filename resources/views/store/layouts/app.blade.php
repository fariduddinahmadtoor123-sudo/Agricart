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
