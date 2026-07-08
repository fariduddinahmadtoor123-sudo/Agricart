<x-filament-panels::page>
    <div @class([
        'agricart-placeholder',
        'agricart-placeholder--coming-soon' => $this->isComingSoonModulePage(),
    ])>
        @if ($title = $this->getPlaceholderTitle())
            <h2 class="agricart-placeholder__title">{{ $title }}</h2>
        @endif
        <p class="agricart-placeholder__message">
            {{ $this->getPlaceholderMessage() }}
        </p>
    </div>
</x-filament-panels::page>
