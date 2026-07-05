@php
    use Filament\Support\Icons\Heroicon;

    $filters = $toolbar['filters'] ?? [];
    $moduleKey = $moduleKey ?? 'module';
@endphp

<div
    class="agricart-toolbar-dropdown"
    x-data="{
        open: false,
        query: '',
        matches(label) {
            return this.query.trim() === '' || label.toLowerCase().includes(this.query.trim().toLowerCase());
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
        Filter
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
                Search filters
            </label>

            <x-filament::input.wrapper
                :prefix-icon="Heroicon::OutlinedMagnifyingGlass"
                inline-prefix
            >
                <input
                    id="agricart-{{ $moduleKey }}-filter-search-{{ $toolbar['resourceSlug'] }}"
                    type="search"
                    autocomplete="off"
                    placeholder="Search filters..."
                    class="fi-input fi-input-has-inline-prefix"
                    x-model="query"
                    x-on:keydown.escape.stop="open = false"
                />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-toolbar-dropdown__panel-body">
            @if (filled($filters))
                <ul class="agricart-toolbar-dropdown__list" role="listbox" aria-label="Filters">
                    @foreach ($filters as $filter)
                        <li
                            role="option"
                            x-show="matches(@js($filter['label']))"
                            x-cloak
                        >
                            <button
                                type="button"
                                class="agricart-toolbar-dropdown__option"
                            >
                                {{ $filter['label'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <p
                    class="agricart-toolbar-dropdown__empty"
                    x-show="query.trim() !== '' && ! @js(collect($filters)->pluck('label')->values()->all()).some((label) => matches(label))"
                    x-cloak
                >
                    No filters match your search.
                </p>
            @else
                <p class="agricart-toolbar-dropdown__empty">No filters available.</p>
            @endif
        </div>
    </div>
</div>
