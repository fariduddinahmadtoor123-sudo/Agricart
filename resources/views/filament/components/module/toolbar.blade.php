@php
    use Filament\Support\Icons\Heroicon;

    /** @var class-string<\App\Filament\Layout\ClusterPageToolbar> $toolbarClass */
    $toolbar = $toolbarClass::config();
    $moduleKey = $moduleKey ?? 'module';
@endphp

@if (filled($toolbar))
    <div class="agricart-catalog-toolbar">
        <div class="agricart-catalog-toolbar__search">
            <label class="fi-sr-only" for="agricart-{{ $moduleKey }}-search-{{ $toolbar['resourceSlug'] }}">
                {{ $toolbar['searchPlaceholder'] }}
            </label>

            <x-filament::input.wrapper
                :prefix-icon="Heroicon::OutlinedMagnifyingGlass"
                inline-prefix
            >
                <input
                    id="agricart-{{ $moduleKey }}-search-{{ $toolbar['resourceSlug'] }}"
                    type="search"
                    autocomplete="off"
                    placeholder="{{ $toolbar['searchPlaceholder'] }}"
                    class="fi-input fi-input-has-inline-prefix agricart-catalog-toolbar__search-input"
                />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-catalog-toolbar__actions">
            @include('filament.components.module.filter-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
            @include('filament.components.module.columns-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])

            @if (filled($toolbar['addLabel']))
                <x-filament::button
                    color="primary"
                    :icon="Heroicon::OutlinedPlus"
                    tag="button"
                    type="button"
                    class="agricart-catalog-toolbar__action"
                    x-on:click="$dispatch('open-form-panel', {
                        title: @js($toolbar['addLabel']),
                        resource: @js($toolbar['resourceLabel']),
                    })"
                >
                    {{ $toolbar['addLabel'] }}
                </x-filament::button>
            @endif
        </div>
    </div>
@endif
