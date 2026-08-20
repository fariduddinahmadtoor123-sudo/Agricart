@php
    $currentPath = trim(request()->path(), '/');
    $navEntries = $menu_nav_entries ?? collect();
@endphp

<nav class="ag-store-menu" aria-label="{{ __('agricart.storefront.menu.label') }}">
    <div class="ag-store__container ag-store-menu__desktop">
        <ul class="ag-store-menu__list">
            @include('store.components.menu-links')
        </ul>
    </div>

    @if (! ($settings->header_enabled ?? true))
        <div class="ag-store-menu__mobile-fallback">
            @include('store.components.menu-toggle')
        </div>
    @endif

    <div class="ag-store-menu-drawer" data-store-menu-drawer aria-hidden="true">
        <div class="ag-store-menu-drawer__backdrop" data-store-menu-backdrop tabindex="-1"></div>
        <aside
            class="ag-store-menu-drawer__panel"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('agricart.storefront.menu.label') }}"
        >
            <div class="ag-store-menu-drawer__head">
                <span class="ag-store-menu-drawer__title">{{ __('agricart.storefront.menu.label') }}</span>
                <button
                    type="button"
                    class="ag-store-menu-drawer__close"
                    data-store-menu-close
                    aria-label="{{ __('agricart.storefront.accessibility.close_menu') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="square" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <ul id="store-mobile-menu" class="ag-store-menu__list" data-store-menu-list>
                @include('store.components.menu-links', ['menuLinkAttributes' => true])
            </ul>
        </aside>
    </div>
</nav>
