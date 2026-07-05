@php
    use Filament\Support\Icons\Heroicon;

    $toolbar = \App\Filament\Layout\CatalogPageToolbar::config();
@endphp

@if (filled($toolbar))
    <div class="agricart-catalog-toolbar">
        <div class="agricart-catalog-toolbar__search">
            <label class="fi-sr-only" for="agricart-catalog-search-{{ $toolbar['resourceSlug'] }}">
                {{ $toolbar['searchPlaceholder'] }}
            </label>

            <x-filament::input.wrapper
                :prefix-icon="Heroicon::OutlinedMagnifyingGlass"
                inline-prefix
            >
                <input
                    id="agricart-catalog-search-{{ $toolbar['resourceSlug'] }}"
                    type="search"
                    autocomplete="off"
                    placeholder="{{ $toolbar['searchPlaceholder'] }}"
                    class="fi-input fi-input-has-inline-prefix agricart-catalog-toolbar__search-input"
                />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-catalog-toolbar__actions">
            @include('filament.components.catalog.filter-dropdown', ['toolbar' => $toolbar])
            @include('filament.components.catalog.columns-dropdown', ['toolbar' => $toolbar])

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
