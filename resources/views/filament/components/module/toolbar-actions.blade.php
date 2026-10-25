@php
    $actions = $toolbar['toolbarActions'] ?? [];
    $livewirePrefix = $toolbar['livewirePrefix'] ?? '';
    $livewireComponent = $toolbar['livewireComponent'] ?? null;
@endphp

@foreach ($actions as $action)
    @php
        /*
         * Prefer $wire.call() over Livewire.dispatch / dispatchTo.
         * Returning BinaryFileResponse (export download) only works for real
         * Livewire actions — browser events ignore the download response.
         */
        if (filled($livewireComponent)) {
            $dispatch = '(() => { const components = Livewire.getByName('
                . json_encode($livewireComponent)
                . '); if (components[0]) { components[0].call('
                . json_encode('handleToolbarAction')
                . ', '
                . json_encode($action['value'])
                . '); } })()';
        } else {
            $dispatch = 'Livewire.dispatch('
                . json_encode($livewirePrefix . '-action')
                . ', { action: '
                . json_encode($action['value'])
                . ' })';
        }

        $clickHandler = ($action['value'] ?? '') === 'print'
            ? 'window.print()'
            : $dispatch;

        if (filled($action['confirm'] ?? null)) {
            $clickHandler = 'if (confirm('.json_encode((string) $action['confirm']).')) { '.$clickHandler.' }';
        }
    @endphp

    @if (filled($action['tab'] ?? null))
        <x-filament::button
            :color="$action['color'] ?? 'gray'"
            :outlined="($action['outlined'] ?? true) === true"
            :icon="$action['icon'] ?? null"
            tag="button"
            type="button"
            x-cloak
            x-show="activeTab === @js($action['tab'])"
            @class([
                'agricart-catalog-toolbar__action',
                'agricart-catalog-toolbar__action--primary' => ($action['color'] ?? 'gray') === 'primary' && ($action['outlined'] ?? true) !== true,
                'agricart-catalog-toolbar__action--compact-label' => ($action['compact'] ?? false) === true,
            ])
            x-on:click="{{ $clickHandler }}"
        >
            {{ $action['label'] }}
        </x-filament::button>
    @elseif (filled($action['screen'] ?? null) && filled($action['formMode'] ?? null))
        <x-filament::button
            :color="$action['color'] ?? 'gray'"
            :outlined="($action['outlined'] ?? true) === true"
            :icon="$action['icon'] ?? null"
            tag="button"
            type="button"
            x-cloak
            x-show="screen === @js($action['screen']) && formMode === @js($action['formMode'])"
            @class([
                'agricart-catalog-toolbar__action',
                'agricart-catalog-toolbar__action--primary' => ($action['color'] ?? 'gray') === 'primary' && ($action['outlined'] ?? true) !== true,
                'agricart-catalog-toolbar__action--compact-label' => ($action['compact'] ?? false) === true,
            ])
            x-on:click="{{ $clickHandler }}"
        >
            {{ $action['label'] }}
        </x-filament::button>
    @elseif (filled($action['screen'] ?? null))
        <x-filament::button
            :color="$action['color'] ?? 'gray'"
            :outlined="($action['outlined'] ?? true) === true"
            :icon="$action['icon'] ?? null"
            tag="button"
            type="button"
            x-cloak
            x-show="screen === @js($action['screen'])"
            @class([
                'agricart-catalog-toolbar__action',
                'agricart-catalog-toolbar__action--primary' => ($action['color'] ?? 'gray') === 'primary' && ($action['outlined'] ?? true) !== true,
                'agricart-catalog-toolbar__action--compact-label' => ($action['compact'] ?? false) === true,
            ])
            x-on:click="{{ $clickHandler }}"
        >
            {{ $action['label'] }}
        </x-filament::button>
    @else
        <x-filament::button
            :color="$action['color'] ?? 'gray'"
            :outlined="($action['outlined'] ?? true) === true"
            :icon="$action['icon'] ?? null"
            tag="button"
            type="button"
            @class([
                'agricart-catalog-toolbar__action',
                'agricart-catalog-toolbar__action--primary' => ($action['color'] ?? 'gray') === 'primary' && ($action['outlined'] ?? true) !== true,
                'agricart-catalog-toolbar__action--compact-label' => ($action['compact'] ?? false) === true,
            ])
            x-on:click="{{ $clickHandler }}"
        >
            {{ $action['label'] }}
        </x-filament::button>
    @endif
@endforeach
