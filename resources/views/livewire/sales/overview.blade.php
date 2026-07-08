<div class="agricart-catalog-overview">
    @if (filled(trim($search)))
        <section class="agricart-catalog-overview__search-results" wire:key="sales-overview-search">
            <h2 class="agricart-catalog-overview__search-title">
                {{ __('agricart.sales.overview.search_results', ['query' => $search]) }}
            </h2>

            @if ($searchResults === [])
                <p class="agricart-catalog-overview__search-empty">
                    {{ __('agricart.sales.overview.search_no_results', ['query' => $search]) }}
                </p>
            @else
                <div class="agricart-catalog-overview__search-groups">
                    @foreach ($searchResults as $groupKey => $items)
                        <section class="agricart-catalog-overview__search-group" wire:key="sales-search-group-{{ $groupKey }}">
                            <h3 class="agricart-catalog-overview__search-group-title">
                                {{ __('agricart.sales.overview.cards.' . $groupKey) }}
                            </h3>

                            <ul class="agricart-catalog-overview__search-list">
                                @foreach ($items as $index => $item)
                                    <li wire:key="sales-search-{{ $groupKey }}-{{ $index }}">
                                        <a href="{{ $item['url'] }}" class="agricart-catalog-overview__search-item">
                                            <span class="agricart-catalog-overview__search-item-label">{{ $item['label'] }}</span>
                                            @if (filled($item['meta']))
                                                <span class="agricart-catalog-overview__search-item-meta">{{ $item['meta'] }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <div class="agricart-catalog-overview__grid">
        @foreach ($stats as $stat)
            <a href="{{ $stat['url'] }}" class="agricart-catalog-overview__card" wire:key="sales-stat-{{ $stat['key'] }}">
                <span class="agricart-catalog-overview__label">{{ $stat['label'] }}</span>
                <span class="agricart-catalog-overview__count">{{ number_format($stat['count']) }}</span>
                @if ($stat['active'] !== null)
                    <span class="agricart-catalog-overview__meta">
                        {{ __('agricart.sales.overview.meta.' . $stat['key'], ['count' => number_format($stat['active'])]) }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</div>
