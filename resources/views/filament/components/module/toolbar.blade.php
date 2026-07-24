@php
    use Filament\Support\Icons\Heroicon;

    /** @var class-string<\App\Filament\Layout\ClusterPageToolbar> $toolbarClass */
    $toolbar = $toolbarClass::config();
    $moduleKey = $moduleKey ?? 'module';
    $livewirePrefix = $toolbar['livewirePrefix'] ?? null;
    $livewireComponent = $toolbar['livewireComponent'] ?? null;
@endphp

@if (filled($toolbar))
    @if (! empty($toolbar['analyticsControls']))
        @include('filament.components.module.analytics-toolbar', [
            'toolbar' => $toolbar,
        ])
    @elseif (($toolbar['resourceSlug'] ?? '') === 'attendance' || ($toolbar['resourceSlug'] ?? '') === 'leave-management' || ($toolbar['resourceSlug'] ?? '') === 'payroll')
        @include('filament.components.module.attendance-toolbar', [
            'toolbar' => $toolbar,
            'moduleKey' => $moduleKey,
        ])
    @else
    @php
        $defaultTab = (string) (($toolbar['segments'][0]['value'] ?? null) ?: 'reorder');
    @endphp
    <div
        @class([
            'agricart-catalog-toolbar',
            'agricart-catalog-toolbar--compact' => ! empty($toolbar['hideSearch']) && ! empty($toolbar['hideFilters']) && ! empty($toolbar['hideColumns']),
            'agricart-catalog-toolbar--segmented' => ! empty($toolbar['segments']),
            'agricart-catalog-toolbar--reorder' => ($toolbar['resourceSlug'] ?? '') === 're-order-center',
        ])
        @if (! empty($toolbar['segments']))
            x-data="{
                activeTab: @js($defaultTab),
                queueCount: 0,
                switchTab(tab) {
                    this.activeTab = tab;
                    window.Livewire.dispatch(@js($livewirePrefix . '-tab'), { tab });
                },
                sync(payload) {
                    const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});

                    if (data.activeTab) {
                        this.activeTab = data.activeTab;
                    }

                    if (data.queueCount !== undefined) {
                        this.queueCount = data.queueCount;
                    }
                },
            }"
            x-on:livewire:init="Livewire.on(@js($livewirePrefix . '-toolbar-sync'), (payload) => sync(payload))"
        @endif
    >
        @if (empty($toolbar['hideSearch']))
            <div @class([
                'agricart-catalog-toolbar__search',
                'agricart-catalog-toolbar__search--segments' => ! empty($toolbar['segments']),
            ])>
                @if (! empty($toolbar['segments']))
                    @include('filament.components.module.toolbar-segments', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
                @endif

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
                                        x-on:input.debounce.300ms="Livewire.dispatchTo(@js($livewireComponent), 'updateSearch', { query: $event.target.value })"
                                    @else
                                        x-on:input.debounce.300ms="window.Livewire.dispatch(@js($livewirePrefix . '-search'), { query: $event.target.value })"
                                    @endif
                                @endif
                        />
                    </x-filament::input.wrapper>
                @endif

                @if (($toolbar['resourceSlug'] ?? '') === 're-order-center')
                    <span
                        id="agricart-reorder-toolbar-code"
                        class="agricart-reorder-toolbar__code"
                        dir="ltr"
                        aria-hidden="true"
                    >—</span>
                @endif
            </div>
        @endif

        <div class="agricart-catalog-toolbar__actions">
            @if (empty($toolbar['hideFilters']) || ! empty($toolbar['filters']) || ! empty($toolbar['filterGroups']))
                @include('filament.components.module.filter-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
            @endif
            @if (empty($toolbar['hideColumns']) || ! empty($toolbar['columns']))
                @include('filament.components.module.columns-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
            @endif

            @if (! empty($toolbar['toolbarActions']))
                @include('filament.components.module.toolbar-actions', ['toolbar' => $toolbar])
            @endif

            @if (filled($toolbar['addLabel']))
                @if (filled($toolbar['addEvent'] ?? null))
                    <x-filament::button
                        color="primary"
                        :icon="$toolbar['actionIcon'] ?? Heroicon::OutlinedPlus"
                        tag="button"
                        type="button"
                        @class([
                            'agricart-catalog-toolbar__action',
                            'agricart-catalog-toolbar__action--compact-label' => ($toolbar['compactAddButton'] ?? false) === true,
                        ])
                        data-agricart-livewire-dispatch="{{ $toolbar['addEvent'] }}"
                    >
                        {{ $toolbar['addLabel'] }}
                    </x-filament::button>
                @else
                    <x-filament::button
                        color="primary"
                        :icon="$toolbar['actionIcon'] ?? Heroicon::OutlinedPlus"
                        tag="button"
                        type="button"
                        @class([
                            'agricart-catalog-toolbar__action',
                            'agricart-catalog-toolbar__action--compact-label' => ($toolbar['compactAddButton'] ?? false) === true,
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
            @endif
        </div>
    </div>
    @endif
@endif
