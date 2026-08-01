@php
    use Filament\Support\Icons\Heroicon;

    $columns = $toolbar['columns'] ?? [];
    $moduleKey = $moduleKey ?? 'module';
    $interactive = ($toolbar['interactive'] ?? false) === true;
    $livewirePrefix = $toolbar['livewirePrefix'] ?? 'ai-logs';
    $livewireComponent = $toolbar['livewireComponent'] ?? null;
    $columnDataAttr = $toolbar['columnDataAttr'] ?? 'data-ai-log-column';
@endphp

<div
    class="agricart-toolbar-dropdown"
    x-data="{
        open: false,
        query: '',
        matches(label) {
            return this.query.trim() === '' || label.toLowerCase().includes(this.query.trim().toLowerCase());
        },
        toggleColumn(column, visible) {
            @if ($interactive)
                @if (filled($livewireComponent))
                    window.Livewire.dispatchTo(@js($livewireComponent), @js($livewirePrefix . '-column-toggle'), { column, visible });
                @else
                    window.Livewire.dispatch(@js($livewirePrefix . '-column-toggle'), { column, visible });
                @endif
            @endif
        },
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
>
    <x-filament::button
        color="gray"
        outlined
        :icon="Heroicon::OutlinedViewColumns"
        tag="button"
        type="button"
        class="agricart-catalog-toolbar__action"
        x-on:click.stop="open = ! open"
        x-bind:aria-expanded="open.toString()"
    >
        {{ __('agricart.toolbar.columns') }}
    </x-filament::button>

    <div
        x-cloak
        x-show="open"
        x-transition:enter-start="agricart-toolbar-dropdown__panel--enter-start"
        x-transition:leave-end="agricart-toolbar-dropdown__panel--leave-end"
        class="agricart-toolbar-dropdown__panel agricart-toolbar-dropdown__panel--columns"
        x-on:click.stop
    >
        <div class="agricart-toolbar-dropdown__panel-search">
            <label class="fi-sr-only" for="agricart-{{ $moduleKey }}-columns-search-{{ $toolbar['resourceSlug'] }}">
                {{ __('agricart.toolbar.search_columns') }}
            </label>

            <x-filament::input.wrapper
                :prefix-icon="Heroicon::OutlinedMagnifyingGlass"
                inline-prefix
            >
                <input
                    id="agricart-{{ $moduleKey }}-columns-search-{{ $toolbar['resourceSlug'] }}"
                    type="search"
                    autocomplete="off"
                    placeholder="{{ __('agricart.toolbar.search_columns') }}..."
                    class="fi-input fi-input-has-inline-prefix"
                    x-model="query"
                    x-on:keydown.escape.stop="open = false"
                />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-toolbar-dropdown__panel-body">
            @if (filled($columns))
                <ul class="agricart-toolbar-dropdown__list agricart-toolbar-dropdown__list--columns" role="listbox" aria-label="{{ __('agricart.toolbar.columns') }}">
                    @foreach ($columns as $column)
                        <li
                            role="option"
                            x-show="matches(@js($column['label']))"
                            x-cloak
                        >
                            <label class="agricart-toolbar-dropdown__column-option">
                                <input
                                    type="checkbox"
                                    value="{{ $column['value'] }}"
                                    @if ($interactive)
                                        {!! ' ' . $columnDataAttr . '="' . e($column['value']) . '"' !!}
                                        x-on:change="toggleColumn(@js($column['value']), $event.target.checked)"
                                    @endif
                                    @checked($column['visible'])
                                />
                                <span>{{ $column['label'] }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>

                <p
                    class="agricart-toolbar-dropdown__empty"
                    x-show="query.trim() !== '' && ! @js(collect($columns)->pluck('label')->values()->all()).some((label) => matches(label))"
                    x-cloak
                >
                    {{ __('agricart.toolbar.no_columns_match') }}
                </p>
            @else
                <p class="agricart-toolbar-dropdown__empty">{{ __('agricart.toolbar.no_columns') }}</p>
            @endif
        </div>
    </div>
</div>
