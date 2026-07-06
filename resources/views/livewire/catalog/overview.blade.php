<div class="agricart-catalog-overview">
    <section class="agricart-catalog-overview__intro">
        <h2 class="agricart-catalog-overview__title">{{ __('agricart.catalog.overview.title') }}</h2>
        <p class="agricart-catalog-overview__hint">{{ __('agricart.catalog.overview.hint') }}</p>
        <p class="agricart-catalog-overview__total">
            {{ __('agricart.catalog.overview.total_records', ['count' => number_format($totalRecords)]) }}
        </p>
    </section>

    <div class="agricart-catalog-overview__grid">
        @foreach ($stats as $stat)
            <a href="{{ $stat['url'] }}" class="agricart-catalog-overview__card" wire:key="catalog-stat-{{ $stat['key'] }}">
                <span class="agricart-catalog-overview__label">{{ $stat['label'] }}</span>
                <span class="agricart-catalog-overview__count">{{ number_format($stat['count']) }}</span>
                @if ($stat['active'] !== null)
                    <span class="agricart-catalog-overview__meta">
                        {{ __('agricart.catalog.overview.active_count', ['count' => number_format($stat['active'])]) }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</div>
