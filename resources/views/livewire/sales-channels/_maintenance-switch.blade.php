@php
    $active = (bool) $channel->maintenance_mode;
    $knobLeft = $active ? '2.875rem' : '0.1875rem';
    $trackBackground = $active
        ? 'linear-gradient(180deg, #fcd34d 0%, #f59e0b 45%, #d97706 100%)'
        : 'linear-gradient(180deg, #d8dce3 0%, #a8afb9 55%, #959dab 100%)';
@endphp

<div class="agricart-sales-channels__status-wrap agricart-sales-channels__status-wrap--maintenance">
    <span
        class="agricart-sales-channels__state-pill @if ($active) is-maintenance @else is-live @endif"
        aria-live="polite"
    >
        {{ $active ? __('sales_channels.maintenance.badge_on') : __('sales_channels.maintenance.badge_off') }}
    </span>

    @if ($canToggle)
        <button
            type="button"
            class="agricart-sales-channels__switch agricart-sales-channels__switch--maintenance @if ($active) is-on @else is-off @endif"
            wire:click="toggleMaintenance({{ $channel->id }})"
            wire:loading.attr="disabled"
            role="switch"
            aria-checked="{{ $active ? 'true' : 'false' }}"
            aria-label="{{ __('sales_channels.actions.toggle_maintenance') }}"
        >
            <span
                class="agricart-sales-channels__switch-track"
                style="background: {{ $trackBackground }};"
                aria-hidden="true"
            >
                @if ($active)
                    <span class="agricart-sales-channels__switch-label agricart-sales-channels__switch-label--on">
                        {{ __('sales_channels.maintenance.switch_on') }}
                    </span>
                @else
                    <span class="agricart-sales-channels__switch-label agricart-sales-channels__switch-label--off">
                        {{ __('sales_channels.maintenance.switch_off') }}
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
            class="agricart-sales-channels__switch is-readonly @if ($active) is-on @else is-off @endif"
            aria-hidden="true"
        >
            <span class="agricart-sales-channels__switch-track" style="background: {{ $trackBackground }};">
                @if ($active)
                    <span class="agricart-sales-channels__switch-label agricart-sales-channels__switch-label--on">
                        {{ __('sales_channels.maintenance.switch_on') }}
                    </span>
                @else
                    <span class="agricart-sales-channels__switch-label agricart-sales-channels__switch-label--off">
                        {{ __('sales_channels.maintenance.switch_off') }}
                    </span>
                @endif
            </span>
            <span class="agricart-sales-channels__switch-knob" style="left: {{ $knobLeft }};"></span>
        </span>
    @endif
</div>
