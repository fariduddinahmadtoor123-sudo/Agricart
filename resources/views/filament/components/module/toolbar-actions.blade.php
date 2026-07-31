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
    @endphp
    @if (filled($action['tab'] ?? null))
        <x-filament::button
            :color="$action['color'] ?? 'gray'"
            :outlined="($action['outlined'] ?? true) === true"
            :icon="$action['icon'] ?? null"
            tag="button"
            type="button"
            x-show="activeTab === @js($action['tab'])"
            x-cloak
            @class([
                'agricart-catalog-toolbar__action',
                'agricart-catalog-toolbar__action--primary' => ($action['color'] ?? 'gray') === 'primary' && ($action['outlined'] ?? true) !== true,
                'agricart-catalog-toolbar__action--compact-label' => ($action['compact'] ?? false) === true,
            ])
            x-on:click="{{ $dispatch }}"
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
            x-on:click="{{ $dispatch }}"
        >
            {{ $action['label'] }}
        </x-filament::button>
    @endif
@endforeach
