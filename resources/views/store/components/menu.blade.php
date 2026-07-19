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
            data-store-menu-toggle
        >{{ __('agricart.storefront.accessibility.open_menu') }}</button>
        <ul id="store-mobile-menu" class="ag-store-menu__list" data-store-menu-list>
            @if ($settings->menu_show_home)
                <li>
                    <a href="{{ route('store.home') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === '']) @if ($currentPath === '') aria-current="page" @endif>
                        {{ app()->getLocale() === 'ur' && filled($settings->menu_home_label_ur) ? $settings->menu_home_label_ur : $settings->menu_home_label_en }}
                    </a>
                </li>
            @endif

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
