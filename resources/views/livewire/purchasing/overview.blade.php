<div class="agricart-purchasing-overview">
    @if (filled(trim($search)))
        <section class="agricart-purchasing-overview__search-results" wire:key="purchasing-overview-search">
            <h2 class="agricart-purchasing-overview__search-title">
                {{ __('agricart.purchasing.overview.search_results', ['query' => $search]) }}
            </h2>

            @if ($searchResults === [])
                <p class="agricart-purchasing-overview__search-empty">
                    {{ __('agricart.purchasing.overview.search_no_results', ['query' => $search]) }}
                </p>
            @else
                <div class="agricart-purchasing-overview__search-groups">
                    @foreach ($searchResults as $groupKey => $items)
                        <section class="agricart-purchasing-overview__search-group" wire:key="purchasing-search-group-{{ $groupKey }}">
                            <h3 class="agricart-purchasing-overview__search-group-title">
                                {{ __('agricart.purchasing.overview.cards.' . $groupKey) }}
                            </h3>

                            <ul class="agricart-purchasing-overview__search-list">
                                @foreach ($items as $index => $item)
                                    <li wire:key="purchasing-search-{{ $groupKey }}-{{ $index }}">
                                        <a href="{{ $item['url'] }}" class="agricart-purchasing-overview__search-item">
                                            <span class="agricart-purchasing-overview__search-item-label">{{ $item['label'] }}</span>
                                            @if (filled($item['meta']))
                                                <span class="agricart-purchasing-overview__search-item-meta">{{ $item['meta'] }}</span>
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

    <section class="agricart-purchasing-overview__intro">
        <h2 class="agricart-purchasing-overview__title">{{ __('agricart.purchasing.overview.title') }}</h2>
        <p class="agricart-purchasing-overview__hint">{{ __('agricart.purchasing.overview.hint') }}</p>
        <p class="agricart-purchasing-overview__total">
            {{ __('agricart.purchasing.overview.total_records', ['count' => number_format($totalRecords)]) }}
        </p>
    </section>

    <div class="agricart-purchasing-overview__grid">
        @foreach ($stats as $stat)
            <a href="{{ $stat['url'] }}" class="agricart-purchasing-overview__card" wire:key="purchasing-stat-{{ $stat['key'] }}">
                <span class="agricart-purchasing-overview__label">{{ $stat['label'] }}</span>
                <span class="agricart-purchasing-overview__count">{{ number_format($stat['count']) }}</span>
                @if ($stat['active'] !== null)
                    <span class="agricart-purchasing-overview__meta">
                        @if ($stat['key'] === 'purchases')
                            {{ __('agricart.purchasing.overview.pending_delivery', ['count' => number_format($stat['active'])]) }}
                        @elseif ($stat['key'] === 're_orders')
                            {{ __('agricart.purchasing.overview.queue_pending', ['count' => number_format($stat['active'])]) }}
                        @elseif ($stat['key'] === 'price_tags')
                            {{ __('agricart.purchasing.overview.missing_sale_price', ['count' => number_format($stat['active'])]) }}
                        @else
                            {{ __('agricart.purchasing.overview.active_count', ['count' => number_format($stat['active'])]) }}
                        @endif
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    <section class="agricart-purchasing-overview__insights">
        <div class="agricart-purchasing-overview__insights-head">
            <h3 class="agricart-purchasing-overview__insights-title">{{ __('agricart.purchasing.overview.insights_title') }}</h3>
            <p class="agricart-purchasing-overview__insights-hint">{{ __('agricart.purchasing.overview.insights_hint') }}</p>
        </div>

        <div class="agricart-purchasing-overview__insights-grid">
            @foreach ($insights as $insight)
                <a href="{{ $insight['url'] }}" class="agricart-purchasing-overview__insight-card" wire:key="purchasing-insight-{{ $insight['key'] }}">
                    <span class="agricart-purchasing-overview__insight-count">{{ number_format($insight['count']) }}</span>
                    <span class="agricart-purchasing-overview__insight-label">{{ $insight['label'] }}</span>
                    @if (filled($insight['hint']))
                        <span class="agricart-purchasing-overview__insight-hint">{{ $insight['hint'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
</div>
