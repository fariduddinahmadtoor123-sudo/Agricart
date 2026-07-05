@php
    $wrapperClass = $wrapperClass ?? 'ag-store-social';
    $linkClass = $linkClass ?? 'ag-store-social__link';
    $networks = $settings->socialNetworks();
@endphp

@if ($settings->hasSocialLinks())
    <div class="{{ $wrapperClass }}">
        @foreach ($networks as $network => $url)
            @if (filled($url))
                <a href="{{ $url }}" class="{{ $linkClass }}" target="_blank" rel="noopener noreferrer" title="{{ ucfirst($network) }}">
                    @include('store.components.icons.' . $network)
                </a>
            @endif
        @endforeach
    </div>
@endif
