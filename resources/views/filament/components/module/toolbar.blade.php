@php
    use Filament\Support\Icons\Heroicon;

    /** @var class-string<\App\Filament\Layout\ClusterPageToolbar> $toolbarClass */
    $toolbar = $toolbarClass::config();
    $moduleKey = $moduleKey ?? 'module';
    $livewirePrefix = $toolbar['livewirePrefix'] ?? null;
@endphp

@if (filled($toolbar))
    <div @class([
        'agricart-catalog-toolbar',
        'agricart-catalog-toolbar--compact' => ! empty($toolbar['hideSearch']) && ! empty($toolbar['hideFilters']) && ! empty($toolbar['hideColumns']),
    ])>
        @if (empty($toolbar['hideSearch']))
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
                    @if ($livewirePrefix)
                        x-on:input.debounce.300ms="window.Livewire.dispatch(@js($livewirePrefix . '-search'), { query: $event.target.value })"
                    @endif
                />
            </x-filament::input.wrapper>
        </div>
        @endif

        <div class="agricart-catalog-toolbar__actions">
            @if (empty($toolbar['hideFilters']))
                @include('filament.components.module.filter-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
            @endif
            @if (empty($toolbar['hideColumns']))
                @include('filament.components.module.columns-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
            @endif

            @if (filled($toolbar['addLabel']))
                <x-filament::button
                    color="primary"
                    :icon="$toolbar['actionIcon'] ?? Heroicon::OutlinedPlus"
                    tag="button"
                    type="button"
                    class="agricart-catalog-toolbar__action"
                    data-agricart-open-form-panel
                    data-form-title="{{ $toolbar['addLabel'] }}"
                    data-form-resource="{{ $toolbar['resourceLabel'] }}"
                    data-form-key="{{ $toolbar['formKey'] ?? '' }}"
                >
                    {{ $toolbar['addLabel'] }}
                </x-filament::button>
            @endif
        </div>
    </div>
@endif
