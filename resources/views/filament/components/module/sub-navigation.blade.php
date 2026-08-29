@php
    /** @var class-string<\App\Filament\Layout\ClusterSubNavigation> $subNavigationClass */
    $items = $subNavigationClass::items();
@endphp

@if (filled($items))
    <nav class="agricart-catalog-subnav" aria-label="{{ \App\Support\AgricartTranslator::module($moduleKey ?? 'app') }}">
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

                        @if (! empty($item['comingSoon']))
                            <span class="agricart-catalog-subnav__coming-soon">{{ $item['comingSoonBadge'] }}</span>
                        @elseif (! empty($item['badgeCount']))
                            <span class="agricart-catalog-subnav__badge" aria-label="{{ __('agricart.nav.unread_count', ['count' => $item['badgeCount']]) }}">
                                {{ $item['badgeCount'] > 99 ? '99+' : $item['badgeCount'] }}
                            </span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
