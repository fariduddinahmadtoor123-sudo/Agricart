@php
    $wrapperClass = $wrapperClass ?? 'ag-store-topbar__locale';
    $currentLocale = app()->getLocale();
    $languages = [
        'en' => __('agricart.storefront.language.english'),
        'ur' => __('agricart.storefront.language.urdu'),
    ];
    $currentLabel = $languages[$currentLocale] ?? $languages['en'];
@endphp
<details class="{{ $wrapperClass }} ag-store-locale-details">
    <summary class="ag-store-topbar__locale-trigger" aria-label="{{ __('agricart.storefront.language.label') }}">
        <span class="ag-store-topbar__locale-current">{{ $currentLabel }}</span>
        <span class="ag-store-topbar__locale-chevron" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </span>
    </summary>
    <ul class="ag-store-topbar__locale-menu" role="listbox" aria-label="{{ __('agricart.storefront.language.label') }}">
        @foreach ($languages as $code => $label)
            <li role="presentation">
                <a
                    href="{{ route('agricart.locale', ['locale' => $code, 'redirect' => request()->getRequestUri()]) }}"
                    role="option"
                    @class(['ag-store-topbar__locale-option', 'is-active' => $currentLocale === $code])
                    @if ($currentLocale === $code) aria-selected="true" @endif
                >{{ $label }}</a>
            </li>
        @endforeach
    </ul>
</details>
