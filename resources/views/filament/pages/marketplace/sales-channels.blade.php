<x-filament-panels::page>
    {{-- Fallback if Vite theme is not rebuilt: switch + pill still render correctly --}}
    <style>
        .agricart-sales-channels__status-wrap{display:flex;align-items:center;flex-wrap:wrap;gap:.625rem}
        .agricart-sales-channels__state-pill{display:inline-block;min-width:5.5rem;padding:.35rem .65rem;border-radius:999px;font-size:.75rem;font-weight:800;text-align:center;border:1px solid transparent}
        .agricart-sales-channels__state-pill.is-on{background:#dcfce7;border-color:#86efac;color:#166534}
        .agricart-sales-channels__state-pill.is-off{background:#f3f4f6;border-color:#d1d5db;color:#4b5563}
        .agricart-sales-channels__state-pill.is-maintenance{background:#fef3c7;border-color:#fcd34d;color:#92400e}
        .agricart-sales-channels__state-pill.is-live{background:#eff6ff;border-color:#93c5fd;color:#1d4ed8}
        button.agricart-sales-channels__switch,span.agricart-sales-channels__switch{position:relative;display:inline-block;box-sizing:border-box;width:4.625rem;height:1.75rem;padding:0;border:2px solid #a8b0bb;border-radius:999px;background:linear-gradient(180deg,#e3e6eb,#b8bec8);vertical-align:middle;font:inherit;line-height:1;appearance:none}
        button.agricart-sales-channels__switch{cursor:pointer}
        .agricart-sales-channels__switch-track{position:absolute;inset:.1875rem;border-radius:999px;overflow:hidden}
        .agricart-sales-channels__switch-label{position:absolute;top:50%;transform:translateY(-50%);font-size:.6875rem;font-weight:800;letter-spacing:.06em;white-space:nowrap}
        .agricart-sales-channels__switch-label--on{left:.45rem;color:#2d5c18}
        .agricart-sales-channels__switch-label--off{right:.3rem;color:#5c6370}
        .agricart-sales-channels__switch-knob{position:absolute;top:.1875rem;width:1.375rem;height:1.375rem;border-radius:50%;background:radial-gradient(circle at 32% 28%,#fff,#b0b7c2 70%,#8a929e);box-shadow:0 2px 3px rgba(0,0,0,.28);z-index:1}
    </style>

    @script
        <script>
            Livewire.on('sales-channels-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-sales-channel-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:sales-channels.list-sales-channels />
</x-filament-panels::page>
