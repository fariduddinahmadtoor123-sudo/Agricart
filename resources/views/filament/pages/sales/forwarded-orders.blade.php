<x-filament-panels::page>
    @script
        <script>
            Livewire.on('forwarded-orders-clear-search', () => {
                const input = document.getElementById('agricart-sales-search-forwarded-orders');

                if (input) {
                    input.value = '';
                }
            });
        </script>
    @endscript

    <livewire:online-sale-orders.list-forwarded-orders />
</x-filament-panels::page>
