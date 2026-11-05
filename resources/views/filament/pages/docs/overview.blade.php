<x-filament-panels::page>
    <div class="agricart-docs-overview">
        <div class="agricart-docs-overview__intro">
            <p>{{ __('documentation.overview.intro') }}</p>
            <p>{{ __('documentation.overview.structure') }}</p>
        </div>

        <div class="agricart-docs-overview__actions">
            <a href="{{ \App\Filament\Clusters\Docs\AppGuidePage::getUrl() }}?lang=ur" class="agricart-docs-overview__cta">
                {{ __('documentation.overview.open_guide') }}
            </a>
        </div>
    </div>
</x-filament-panels::page>
