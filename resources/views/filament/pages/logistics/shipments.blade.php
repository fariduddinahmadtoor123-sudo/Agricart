<x-filament-panels::page>
    @script
        <script>
            Livewire.on('pending-shipments-columns-sync', (payload) => {
                const columns = payload?.columns ?? payload?.[0]?.columns ?? [];

                document.querySelectorAll('[data-pending-shipment-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });

            Livewire.on('pending-shipments-clear-search', () => {
                const input = document.getElementById('agricart-logistics-search-shipments');

                if (input) {
                    input.value = '';
                }
            });
        </script>
    @endscript

    @script
        <script>
            Livewire.on('online-sale-orders-open-print', (payload) => {
                const url = payload?.url ?? payload?.[0]?.url;

                if (url) {
                    window.open(url, '_blank');
                }
            });
        </script>
    @endscript

    <livewire:logistics.list-pending-shipments />
</x-filament-panels::page>
