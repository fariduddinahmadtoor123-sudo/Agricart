@php
    /** @var class-string<\App\Filament\Layout\ClusterSubNavigation> $subNavigationClass */
    $items = $subNavigationClass::items();
@endphp

@if (filled($items))
    <nav class="agricart-catalog-subnav" aria-label="{{ $navLabel }}">
        <ul class="agricart-catalog-subnav__list">
            @foreach ($items as $item)
                <li class="agricart-catalog-subnav__list-item">
                    <a
                        href="{{ $item['url'] }}"
                        wire:navigate
                        @class([
                            'agricart-catalog-subnav__item',
                            'is-active' => $item['active'],
                        ])
                    >
                        @if (filled($item['icon']))
                            <x-filament::icon
                                :icon="$item['icon']"
                                class="agricart-catalog-subnav__icon"
                            />
                        @endif

                        <span class="agricart-catalog-subnav__label">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
