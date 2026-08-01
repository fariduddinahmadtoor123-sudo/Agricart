@php
    use Filament\Support\Icons\Heroicon;

    $filters = $toolbar['filters'] ?? [];
    $filterGroups = $toolbar['filterGroups'] ?? [];
    $moduleKey = $moduleKey ?? 'module';
    $interactive = ($toolbar['interactive'] ?? false) === true;
    $livewirePrefix = $toolbar['livewirePrefix'] ?? 'ai-logs';
    $livewireComponent = $toolbar['livewireComponent'] ?? null;
@endphp

<div
    class="agricart-toolbar-dropdown"
    x-data="{
        open: false,
        query: '',
        matches(label) {
            return this.query.trim() === '' || label.toLowerCase().includes(this.query.trim().toLowerCase());
        },
        applyFilter(value) {
            @if ($interactive)
                @if (filled($livewireComponent))
                    window.Livewire.dispatchTo(@js($livewireComponent), @js($livewirePrefix . '-filter'), { filter: value });
                @else
                    window.Livewire.dispatch(@js($livewirePrefix . '-filter'), { filter: value });
                @endif
            @endif
            this.open = false;
        },
        clearFilters() {
            @if ($interactive)
                @if (filled($livewireComponent))
                    window.Livewire.dispatchTo(@js($livewireComponent), @js($livewirePrefix . '-clear-filters'));
                @else
                    window.Livewire.dispatch(@js($livewirePrefix . '-clear-filters'));
                @endif
            @endif
            this.open = false;
        },
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
>
    <x-filament::button
        color="gray"
        outlined
        :icon="Heroicon::OutlinedFunnel"
        tag="button"
        type="button"
        class="agricart-catalog-toolbar__action"
        x-on:click.stop="open = ! open"
        x-bind:aria-expanded="open.toString()"
    >
        {{ __('agricart.toolbar.filter') }}
    </x-filament::button>

    <div
        x-cloak
        x-show="open"
        x-transition:enter-start="agricart-toolbar-dropdown__panel--enter-start"
        x-transition:leave-end="agricart-toolbar-dropdown__panel--leave-end"
        class="agricart-toolbar-dropdown__panel agricart-toolbar-dropdown__panel--filter"
        x-on:click.stop
    >
        <div class="agricart-toolbar-dropdown__panel-search">
            <label class="fi-sr-only" for="agricart-{{ $moduleKey }}-filter-search-{{ $toolbar['resourceSlug'] }}">
                {{ __('agricart.toolbar.search_filters') }}
            </label>

            <x-filament::input.wrapper
                :prefix-icon="Heroicon::OutlinedMagnifyingGlass"
                inline-prefix
            >
                <input
                    id="agricart-{{ $moduleKey }}-filter-search-{{ $toolbar['resourceSlug'] }}"
                    type="search"
                    autocomplete="off"
                    placeholder="{{ __('agricart.toolbar.search_filters') }}..."
                    class="fi-input fi-input-has-inline-prefix"
                    x-model="query"
                    x-on:keydown.escape.stop="open = false"
                />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-toolbar-dropdown__panel-body">
            @if ($interactive)
                <button
                    type="button"
                    class="agricart-toolbar-dropdown__option agricart-toolbar-dropdown__option--clear"
                    x-on:click="clearFilters()"
                >
                    {{ __('agricart.ai.logs.clear_filters') }}
                </button>
            @endif

            @if (filled($filterGroups))
                @foreach ($filterGroups as $group)
                    <p class="agricart-toolbar-dropdown__group-label">{{ $group['label'] }}</p>

                    <ul class="agricart-toolbar-dropdown__list" role="listbox" aria-label="{{ $group['label'] }}">
                        @foreach ($group['filters'] as $filter)
                            <li
                                role="option"
                                x-show="matches(@js($filter['label']))"
                                x-cloak
                            >
                                <button
                                    type="button"
                                    class="agricart-toolbar-dropdown__option"
                                    @if ($interactive)
                                        x-on:click="applyFilter(@js($filter['value']))"
                                    @endif
                                >
                                    {{ $filter['label'] }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            @elseif (filled($filters))
                <ul class="agricart-toolbar-dropdown__list" role="listbox" aria-label="{{ __('agricart.toolbar.filter') }}">
                    @foreach ($filters as $filter)
                        <li
                            role="option"
                            x-show="matches(@js($filter['label']))"
                            x-cloak
                        >
                            <button
                                type="button"
                                class="agricart-toolbar-dropdown__option"
                                @if ($interactive)
                                    x-on:click="applyFilter(@js($filter['value']))"
                                @endif
                            >
                                {{ $filter['label'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="agricart-toolbar-dropdown__empty">{{ __('agricart.toolbar.no_filters') }}</p>
            @endif
        </div>
    </div>
</div>
