<div class="agricart-catalog-overview">
    <div class="agricart-catalog-overview__grid">
        @foreach ($stats as $stat)
            <a href="{{ $stat['url'] }}" class="agricart-catalog-overview__card" wire:key="finance-stat-{{ $stat['key'] }}">
                <span class="agricart-catalog-overview__label">{{ $stat['label'] }}</span>
                <span class="agricart-catalog-overview__count">
                    @if (is_numeric($stat['count']))
                        {{ number_format((int) $stat['count']) }}
                    @else
                        {{ $stat['count'] }}
                    @endif
                </span>
                @if (filled($stat['meta'] ?? null))
                    <span class="agricart-catalog-overview__meta">{{ $stat['meta'] }}</span>
                @endif
            </a>
        @endforeach
    </div>
</div>
