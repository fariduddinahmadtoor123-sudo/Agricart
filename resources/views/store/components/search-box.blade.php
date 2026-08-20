@props([
    'value' => '',
    'placeholder' => null,
    'suggestId' => 'ag-store-search-suggest',
])

@php
    $placeholder ??= __('agricart.storefront.header.search');
    $value = (string) $value;
@endphp

<div
    class="ag-store-header__search ag-store-header__search--live"
    data-store-search
    data-store-search-suggest-url="{{ route('store.search.suggest') }}"
    data-store-search-min-chars="2"
    data-store-search-loading="{{ __('agricart.storefront.search.loading') }}"
    data-store-search-count="{{ __('agricart.storefront.search.result_count') }}"
    data-store-search-error="{{ __('agricart.storefront.search.error') }}"
    data-store-search-empty="{{ __('agricart.storefront.search.suggest_empty') }}"
>
    <form action="{{ route('store.search') }}" method="get" class="ag-store-header__search-form" role="search" data-store-search-form>
        <label class="ag-store-visually-hidden" for="{{ $suggestId }}-input">{{ __('agricart.storefront.header.search') }}</label>
        <input
            id="{{ $suggestId }}-input"
            type="search"
            name="q"
            class="ag-store-header__search-input"
            placeholder="{{ $placeholder }}"
            value="{{ $value }}"
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-controls="{{ $suggestId }}"
            data-store-search-input
            enterkeyhint="search"
        />
        <button type="submit" class="ag-store-header__search-btn ag-store-header__search-btn--submit" title="{{ __('agricart.storefront.header.search') }}" aria-label="{{ __('agricart.storefront.header.search') }}">
            <svg class="ag-store-header__search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path d="M20 20l-3.5-3.5"/>
            </svg>
            <span class="ag-store-visually-hidden">{{ __('agricart.storefront.header.search') }}</span>
        </button>
    </form>
    <div id="{{ $suggestId }}" class="ag-store-search-suggestions" role="listbox" hidden data-store-search-list></div>
    <p class="ag-store-visually-hidden" aria-live="polite" aria-atomic="true" data-store-search-status></p>
</div>
