@php
    use Filament\Support\Icons\Heroicon;

    $livewirePrefix = $toolbar['livewirePrefix'] ?? 'staff-attendance';
    $livewireComponent = $toolbar['livewireComponent'] ?? null;
    $moduleKey = $moduleKey ?? 'module';
@endphp

<div
    class="agricart-catalog-toolbar agricart-catalog-toolbar--attendance"
    style="display: flex; flex-wrap: nowrap; align-items: center; justify-content: space-between; gap: 0.75rem; width: 100%;"
>
    <div
        class="agricart-catalog-toolbar__search agricart-catalog-toolbar__search--attendance-inline"
        style="display: flex; flex-wrap: nowrap; align-items: center; gap: 0.5rem; flex: 1 1 auto; min-width: 0;"
    >
        @include('filament.components.module.attendance-toolbar-date', ['toolbar' => $toolbar])

        @if (empty($toolbar['hideSearchInput']))
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
                        @if (filled($livewireComponent))
                            x-on:input.debounce.300ms="Livewire.dispatchTo(@js($livewireComponent), @js($livewirePrefix . '-search'), { query: $event.target.value })"
                        @else
                            x-on:input.debounce.300ms="window.Livewire.dispatch(@js($livewirePrefix . '-search'), { query: $event.target.value })"
                        @endif
                    @endif
                />
            </x-filament::input.wrapper>
        @endif
    </div>

    <div class="agricart-catalog-toolbar__actions" style="flex: 0 0 auto; width: auto;">
        @if (empty($toolbar['hideFilters']))
            @include('filament.components.module.filter-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
        @endif
        @if (empty($toolbar['hideColumns']))
            @include('filament.components.module.columns-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
        @endif

        @if (! empty($toolbar['toolbarActions']))
            @include('filament.components.module.toolbar-actions', ['toolbar' => $toolbar])
        @endif

        @if (filled($toolbar['addLabel']))
            <x-filament::button
                color="primary"
                :icon="$toolbar['actionIcon'] ?? Heroicon::OutlinedPlus"
                tag="button"
                type="button"
                @class([
                    'agricart-catalog-toolbar__action',
                    'agricart-catalog-toolbar__action--compact-label' => ($toolbar['compactAddButton'] ?? true) === true,
                ])
                data-agricart-open-form-panel
                data-form-title="{{ $toolbar['addLabel'] }}"
                data-form-resource="{{ $toolbar['resourceLabel'] }}"
                data-form-key="{{ $toolbar['formKey'] ?? '' }}"
                data-form-context="{{ $toolbar['formContext'] ?? '' }}"
            >
                {{ $toolbar['addLabel'] }}
            </x-filament::button>
        @endif
    </div>
</div>
