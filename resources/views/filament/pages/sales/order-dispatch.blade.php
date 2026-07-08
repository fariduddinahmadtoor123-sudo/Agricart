<x-filament-panels::page>
    @script
        <script>
            Livewire.on('order-dispatch-columns-sync', (payload) => {
                const columns = payload?.columns ?? payload?.[0]?.columns ?? [];

                document.querySelectorAll('[data-order-dispatch-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });

            Livewire.on('order-dispatch-clear-search', () => {
                const input = document.getElementById('agricart-sales-search-order-dispatch');

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

    <livewire:online-sale-orders.list-order-dispatch />
</x-filament-panels::page>
