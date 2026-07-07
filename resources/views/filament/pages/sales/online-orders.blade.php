<x-filament-panels::page>
    @script
        <script>
            Livewire.on('online-orders-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-online-order-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:online-sale-orders.list-online-sale-orders />
</x-filament-panels::page>
