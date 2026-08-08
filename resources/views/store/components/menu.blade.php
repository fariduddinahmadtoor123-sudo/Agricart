@php
    $currentPath = trim(request()->path(), '/');
    $navEntries = $menu_nav_entries ?? collect();
@endphp

<nav class="ag-store-menu" aria-label="{{ __('agricart.storefront.menu.label') }}">
    <div class="ag-store__container">
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
        <ul id="store-mobile-menu" class="ag-store-menu__list" data-store-menu-list>
            @if ($settings->menu_show_home)
                <li>
                    <a href="{{ route('store.home') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === '']) @if ($currentPath === '') aria-current="page" @endif>
                        {{ app()->getLocale() === 'ur' && filled($settings->menu_home_label_ur) ? $settings->menu_home_label_ur : $settings->menu_home_label_en }}
                    </a>
                </li>
            @endif

            <li>
                <a href="{{ route('store.products') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === 'products']) @if ($currentPath === 'products') aria-current="page" @endif>
                    {{ __('agricart.storefront.products.nav') }}
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
                        <a href="{{ $page->publicUrl() }}" @class(['ag-store-menu__link', 'is-active' => $isActive]) @if ($isActive) aria-current="page" @endif>
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
                        >
                            {{ $item->localizedLabel() }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</nav>
