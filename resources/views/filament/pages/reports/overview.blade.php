<x-filament-panels::page>
    <div class="agricart-reports-overview">
        <p class="agricart-reports-overview__lead">{{ __('reports.overview.lead') }}</p>

        <ul class="agricart-reports-overview__grid">
            @foreach ($this->reportLinks() as $link)
                <li wire:key="report-hub-{{ $link['label'] }}">
                    <a href="{{ $link['url'] }}" class="agricart-reports-overview__card">
                        <strong class="agricart-reports-overview__title">{{ $link['label'] }}</strong>
                        <span class="agricart-reports-overview__desc">{{ $link['description'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</x-filament-panels::page>
