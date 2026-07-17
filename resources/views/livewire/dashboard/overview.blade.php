<div class="agricart-catalog-overview">
    <div class="agricart-catalog-overview__grid">
        @foreach ($cards as $card)
            <a href="{{ $card['url'] }}" class="agricart-catalog-overview__card" wire:key="dashboard-card-{{ $card['key'] }}">
                <span class="agricart-catalog-overview__label">{{ $card['label'] }}</span>
                <span class="agricart-catalog-overview__count">
                    {{ is_int($card['value']) ? number_format($card['value']) : $card['value'] }}
                </span>
                <span class="agricart-catalog-overview__meta">{{ $card['meta'] }}</span>
            </a>
        @endforeach
    </div>

    <section class="agricart-purchasing-overview__insights">
        <div class="agricart-purchasing-overview__insights-head">
            <h3 class="agricart-purchasing-overview__insights-title">{{ __('agricart.dashboard.attention.title') }}</h3>
            <p class="agricart-purchasing-overview__insights-hint">{{ __('agricart.dashboard.attention.hint') }}</p>
        </div>

        <div class="agricart-purchasing-overview__insights-grid">
            @foreach ($attention as $item)
                <a href="{{ $item['url'] }}" class="agricart-purchasing-overview__insight-card" wire:key="dashboard-attention-{{ $item['key'] }}">
                    <span class="agricart-purchasing-overview__insight-count">{{ number_format((int) $item['value']) }}</span>
                    <span class="agricart-purchasing-overview__insight-label">{{ $item['label'] }}</span>
                    <span class="agricart-purchasing-overview__insight-hint">{{ $item['meta'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
</div>
