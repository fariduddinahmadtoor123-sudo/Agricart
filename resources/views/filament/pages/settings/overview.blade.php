<x-filament-panels::page>
    <nav class="agricart-settings-overview" aria-label="Settings overview">
        @foreach (\App\Filament\Layout\SettingsSubNavigation::items() as $item)
            @continue($item['active'])
            <a href="{{ $item['url'] }}" wire:navigate class="agricart-settings-overview__link">
                <strong>{{ $item['label'] }}</strong>
            </a>
        @endforeach
    </nav>
</x-filament-panels::page>
