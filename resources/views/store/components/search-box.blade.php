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
    data-store-search-live-url="{{ route('store.search') }}"
    data-store-search-fragment-url="{{ route('store.search.fragment') }}"
    data-store-search-min-chars="2"
>
    <form action="{{ route('store.search') }}" method="get" class="ag-store-header__search-form" role="search" data-store-search-form>
        <input
            type="search"
            name="q"
            class="ag-store-header__search-input"
            placeholder="{{ $placeholder }}"
            value="{{ $value }}"
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false"
            data-store-search-input
            enterkeyhint="search"
        />
        <button type="submit" class="ag-store-header__search-btn ag-store-header__search-btn--submit" title="{{ __('agricart.storefront.header.search') }}">
            <span class="ag-store-header__search-btn-text">{{ __('agricart.storefront.header.search') }}</span>
        </button>
    </form>
</div>
