@php
    $currentPath = trim(request()->path(), '/');
    $navEntries = $menu_nav_entries ?? collect();
    $brandsActive = $currentPath === 'brands' || str_starts_with($currentPath, 'brand/');
@endphp

@if ($settings->menu_show_home)
    <li>
        <a href="{{ route('store.home') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === '']) @if ($currentPath === '') aria-current="page" @endif @if ($menuLinkAttributes ?? false) data-store-menu-link @endif>
            {{ app()->getLocale() === 'ur' && filled($settings->menu_home_label_ur) ? $settings->menu_home_label_ur : $settings->menu_home_label_en }}
        </a>
    </li>
@endif

<li>
    <a href="{{ route('store.products') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === 'products']) @if ($currentPath === 'products') aria-current="page" @endif @if ($menuLinkAttributes ?? false) data-store-menu-link @endif>
        {{ __('agricart.storefront.products.nav') }}
    </a>
</li>

<li>
    <a href="{{ route('store.brands') }}" @class(['ag-store-menu__link', 'is-active' => $brandsActive]) @if ($brandsActive) aria-current="page" @endif @if ($menuLinkAttributes ?? false) data-store-menu-link @endif>
        {{ __('agricart.storefront.brands.nav') }}
    </a>
</li>

<li>
    <a href="{{ route('store.blog') }}" @class(['ag-store-menu__link', 'is-active' => $currentPath === 'blog' || str_starts_with($currentPath, 'blog/')]) @if ($currentPath === 'blog' || str_starts_with($currentPath, 'blog/')) aria-current="page" @endif @if ($menuLinkAttributes ?? false) data-store-menu-link @endif>
        {{ __('agricart.storefront.blog.nav') }}
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
            <a href="{{ $page->publicUrl() }}" @class(['ag-store-menu__link', 'is-active' => $isActive]) @if ($isActive) aria-current="page" @endif @if ($menuLinkAttributes ?? false) data-store-menu-link @endif>
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
                @if ($menuLinkAttributes ?? false) data-store-menu-link @endif
            >
                {{ $item->localizedLabel() }}
            </a>
        </li>
    @endif
@endforeach
