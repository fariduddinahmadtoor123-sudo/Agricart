@php
    $currentPath = trim(request()->path(), '/');
@endphp

<nav class="ag-store-menu" aria-label="{{ __('agricart.storefront.menu.label') }}">
    <div class="ag-store__container">
        <ul class="ag-store-menu__list">
            @if ($settings->menu_show_home)
                <li>
                    <a href="{{ route('store.home') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === ''])>
                        {{ app()->getLocale() === 'ur' && filled($settings->menu_home_label_ur) ? $settings->menu_home_label_ur : $settings->menu_home_label_en }}
                    </a>
                </li>
            @endif

            @foreach ($menu_items as $item)
                @php
                    $itemPath = ltrim(parse_url($item->resolvedUrl(), PHP_URL_PATH) ?? '', '/');
                    $isActive = filled($itemPath) && $itemPath !== '' && ($currentPath === $itemPath || str_starts_with($currentPath, $itemPath . '/'));
                @endphp
                <li>
                    <a
                        href="{{ $item->resolvedUrl() }}"
                        @class(['ag-store-menu__link', 'is-active' => $isActive])
                        @if ($item->opens_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                    >
                        {{ $item->localizedLabel() }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
