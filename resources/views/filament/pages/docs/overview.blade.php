<x-filament-panels::page>
    <div class="agricart-docs-overview">
        <div class="agricart-docs-overview__intro">
            <p>{{ __('documentation.overview.intro') }}</p>
            <p>{{ __('documentation.overview.structure') }}</p>
        </div>

        <div class="agricart-docs-overview__actions">
            <a href="{{ \App\Filament\Clusters\Docs\GuidePage::guideUrl('contacts', 'customers', ['mode' => 'brief', 'lang' => 'ur', 'kind' => 'form']) }}" class="agricart-docs-overview__cta">
                {{ __('documentation.overview.open_guide') }}
            </a>
        </div>

        <div class="agricart-docs-overview__grid">
            @foreach (\App\Support\Documentation\DocumentationRegistry::modules() as $module)
                <a
                    href="{{ \App\Filament\Clusters\Docs\GuidePage::guideUrl(
                        $module['key'],
                        \App\Support\Documentation\DocumentationRegistry::defaultPageKey($module['key'])
                    ) }}"
                    @class([
                        'agricart-docs-overview__card',
                        'is-coming-soon' => $module['comingSoon'],
                    ])
                >
                    <span class="agricart-docs-overview__card-title">{{ $module['label'] }}</span>
                    @if ($module['comingSoon'])
                        <span class="agricart-docs-overview__badge">{{ __('documentation.badges.coming_soon_module') }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
