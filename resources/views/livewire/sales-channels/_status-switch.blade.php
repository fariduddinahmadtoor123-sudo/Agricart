@php
    $enabled = (bool) $channel->is_enabled;
    $knobLeft = $enabled ? '2.875rem' : '0.1875rem';
    $trackBackground = $enabled
        ? 'linear-gradient(180deg, #b8e86a 0%, #7bc93a 45%, #5fa828 100%)'
        : 'linear-gradient(180deg, #d8dce3 0%, #a8afb9 55%, #959dab 100%)';
@endphp

<div class="agricart-sales-channels__status-wrap">
    <span
        class="agricart-sales-channels__state-pill @if ($enabled) is-on @else is-off @endif"
        aria-live="polite"
    >
        {{ $enabled ? __('sales_channels.status.badge_on') : __('sales_channels.status.badge_off') }}
    </span>

    @if ($canToggle)
        <button
            type="button"
            class="agricart-sales-channels__switch @if ($enabled) is-on @else is-off @endif"
            wire:click="toggleChannel({{ $channel->id }})"
            wire:loading.attr="disabled"
            role="switch"
            aria-checked="{{ $enabled ? 'true' : 'false' }}"
            aria-label="{{ __('sales_channels.actions.toggle_store') }}"
        >
            <span
                class="agricart-sales-channels__switch-track"
                style="background: {{ $trackBackground }};"
                aria-hidden="true"
            >
                @if ($enabled)
                    <span class="agricart-sales-channels__switch-label agricart-sales-channels__switch-label--on">
                        {{ __('sales_channels.status.on') }}
                    </span>
                @else
                    <span class="agricart-sales-channels__switch-label agricart-sales-channels__switch-label--off">
                        {{ __('sales_channels.status.off') }}
                    </span>
                @endif
            </span>
            <span
                class="agricart-sales-channels__switch-knob"
                style="left: {{ $knobLeft }};"
                aria-hidden="true"
            ></span>
        </button>
    @else
        <span
            class="agricart-sales-channels__switch is-readonly @if ($enabled) is-on @else is-off @endif"
            aria-hidden="true"
        >
            <span class="agricart-sales-channels__switch-track" style="background: {{ $trackBackground }};">
                @if ($enabled)
                    <span class="agricart-sales-channels__switch-label agricart-sales-channels__switch-label--on">
                        {{ __('sales_channels.status.on') }}
                    </span>
                @else
                    <span class="agricart-sales-channels__switch-label agricart-sales-channels__switch-label--off">
                        {{ __('sales_channels.status.off') }}
                    </span>
                @endif
            </span>
            <span class="agricart-sales-channels__switch-knob" style="left: {{ $knobLeft }};"></span>
        </span>
    @endif
</div>
