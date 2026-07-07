<x-filament-panels::page>
    <livewire:re-order.re-order-center />
</x-filament-panels::page>

@once
    <script>
        (() => {
            if (window.__agricartReorderToolbarReady) {
                return;
            }

            window.__agricartReorderToolbarReady = true;
            window.__agricartReorderLastCode = '—';

            window.agricartApplyReorderOrderCode = (encoded) => {
                window.__agricartReorderLastCode = encoded || '—';
                document.querySelectorAll('#agricart-reorder-toolbar-code').forEach((el) => {
                    el.textContent = window.__agricartReorderLastCode;
                });
            };

            const pullCode = (payload) => {
                let encoded = '—';

                if (typeof payload === 'string') {
                    encoded = payload;
                } else if (payload?.encoded) {
                    encoded = payload.encoded;
                } else if (Array.isArray(payload) && payload[0]?.encoded) {
                    encoded = payload[0].encoded;
                }

                window.agricartApplyReorderOrderCode(encoded);
            };

            document.addEventListener('livewire:init', () => {
                Livewire.on('reorder-order-code', pullCode);
                Livewire.on('reorder-open-print', (payload) => {
                    const url = payload?.url ?? payload?.[0]?.url;
                    if (url) {
                        window.open(url, '_blank');
                    }
                });
            });

            const slot = document.getElementById('agricart-module-chrome-slot');
            if (slot) {
                let repaintTimer = null;
                new MutationObserver(() => {
                    if (repaintTimer) {
                        clearTimeout(repaintTimer);
                    }

                    repaintTimer = setTimeout(() => {
                        window.agricartApplyReorderOrderCode(window.__agricartReorderLastCode);
                    }, 50);
                }).observe(slot, { childList: true, subtree: true });
            }
        })();
    </script>
@endonce
