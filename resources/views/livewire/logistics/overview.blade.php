<div class="agricart-catalog-overview">
    <div class="agricart-catalog-overview__grid">
        @foreach ($stats as $stat)
            <a href="{{ $stat['url'] }}" class="agricart-catalog-overview__card" wire:key="logistics-stat-{{ $stat['key'] }}">
                <span class="agricart-catalog-overview__card-label">{{ $stat['label'] }}</span>
                <strong class="agricart-catalog-overview__card-count">{{ number_format($stat['count']) }}</strong>
                @if ($stat['active'] !== null)
                    <span class="agricart-catalog-overview__card-meta">
                        {{ __('agricart.logistics.overview.active_count', ['count' => number_format($stat['active'])]) }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</div>
