@php
    $currentPath = trim(request()->path(), '/');
    $navEntries = $menu_nav_entries ?? collect();
    $brandsActive = $currentPath === 'brands' || str_starts_with($currentPath, 'brand/');
@endphp

<nav class="ag-store-menu" aria-label="{{ __('agricart.storefront.menu.label') }}">
    <div class="ag-store__container ag-store-menu__bar">
        <button
            type="button"
            class="ag-store-menu__disclosure"
            aria-expanded="false"
            aria-controls="store-mobile-menu"
            aria-label="{{ __('agricart.storefront.accessibility.open_menu') }}"
            data-store-menu-toggle
            data-label-open="{{ __('agricart.storefront.accessibility.open_menu') }}"
            data-label-close="{{ __('agricart.storefront.accessibility.close_menu') }}"
        >
            <span class="ag-store-menu__toggle-icon" aria-hidden="true">
                <span class="ag-store-menu__burger">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span class="ag-store-menu__close-x" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="square">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </span>
            </span>
            <span class="ag-store-menu__toggle-label" data-store-menu-label>{{ __('agricart.storefront.menu.toggle') }}</span>
        </button>
    </div>

    <div class="ag-store-menu-drawer" data-store-menu-drawer aria-hidden="true">
        <div class="ag-store-menu-drawer__backdrop" data-store-menu-backdrop tabindex="-1"></div>
        <aside
            class="ag-store-menu-drawer__panel"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('agricart.storefront.menu.label') }}"
        >
            <div class="ag-store-menu-drawer__head">
                <span class="ag-store-menu-drawer__title">{{ __('agricart.storefront.menu.toggle') }}</span>
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

            <div class="ag-store-menu-drawer__body">
                <ul id="store-mobile-menu" class="ag-store-menu__list" data-store-menu-list>
                @if ($settings->menu_show_home)
                    <li>
                        <a href="{{ route('store.home') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === '']) @if ($currentPath === '') aria-current="page" @endif data-store-menu-link>
                            {{ app()->getLocale() === 'ur' && filled($settings->menu_home_label_ur) ? $settings->menu_home_label_ur : $settings->menu_home_label_en }}
                        </a>
                    </li>
                @endif

                <li>
                    <a href="{{ route('store.products') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === 'products']) @if ($currentPath === 'products') aria-current="page" @endif data-store-menu-link>
                        {{ __('agricart.storefront.products.nav') }}
                    </a>
                </li>

                <li>
                    <a href="{{ route('store.brands') }}" @class(['ag-store-menu__link', 'is-active' => $brandsActive]) @if ($brandsActive) aria-current="page" @endif data-store-menu-link>
                        {{ __('agricart.storefront.brands.nav') }}
                    </a>
                </li>

                @foreach ($navEntries as $entry)
                    @if ($entry['type'] === 'page')
                        @php
                            $page = $entry['page'];
                            $pagePath = ltrim(parse_url($page->publicUrl(), PHP_URL_PATH) ?? '', '/');
                            $isActive = $currentPath === $pagePath;
                        @endphp
                        <li>
                            <a href="{{ $page->publicUrl() }}" @class(['ag-store-menu__link', 'is-active' => $isActive]) @if ($isActive) aria-current="page" @endif data-store-menu-link>
                                {{ $page->localizedTitle() }}
                            </a>
                        </li>
                    @else
                        @php
                            $item = $entry['item'];
                            $itemPath = ltrim(parse_url($item->resolvedUrl(), PHP_URL_PATH) ?? '', '/');
                            $isActive = filled($itemPath) && $itemPath !== '' && ($currentPath === $itemPath || str_starts_with($currentPath, $itemPath . '/'));
                        @endphp
                        <li>
                            <a
                                href="{{ $item->resolvedUrl() }}"
                                @class(['ag-store-menu__link', 'is-active' => $isActive])
                                @if ($isActive) aria-current="page" @endif
                                @if ($item->opens_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                data-store-menu-link
                            >
                                {{ $item->localizedLabel() }}
                            </a>
                        </li>
                    @endif
                @endforeach
                </ul>
            </div>
        </aside>
    </div>
</nav>
