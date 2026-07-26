@php
    $wrapperClass = $wrapperClass ?? 'ag-store-header__locale';
    $currentLocale = app()->getLocale();
    $languages = [
        'en' => [
            'label' => __('agricart.storefront.language.english'),
            'short' => __('agricart.storefront.language.english_short'),
        ],
        'ur' => [
            'label' => __('agricart.storefront.language.urdu'),
            'short' => __('agricart.storefront.language.urdu_short'),
        ],
    ];
    $current = $languages[$currentLocale] ?? $languages['en'];
@endphp
<details class="{{ $wrapperClass }} ag-store-locale">
    <summary class="ag-store-locale__trigger" aria-label="{{ __('agricart.storefront.language.label') }}: {{ $current['label'] }}">
        <span class="ag-store-locale__current" data-full="{{ $current['label'] }}" data-short="{{ $current['short'] }}">{{ $current['short'] }}</span>
        <span class="ag-store-locale__chevron" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </span>
    </summary>
    <ul class="ag-store-locale__menu" role="listbox" aria-label="{{ __('agricart.storefront.language.label') }}">
        @foreach ($languages as $code => $language)
            <li role="presentation">
                <a
                    href="{{ route('agricart.locale', ['locale' => $code, 'scope' => 'store', 'redirect' => request()->getRequestUri()]) }}"
                    role="option"
                    @class(['ag-store-locale__option', 'is-active' => $currentLocale === $code])
                    @if ($currentLocale === $code) aria-selected="true" @endif
                >
                    <span class="ag-store-locale__option-short">{{ $language['short'] }}</span>
                    <span class="ag-store-locale__option-label">{{ $language['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</details>
