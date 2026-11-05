<x-filament-panels::page>
    @script
        <script>
            Livewire.on('cancelled-quotations-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-cancelled-quotation-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:online-sale-orders.list-cancelled-online-sale-quotations />
</x-filament-panels::page>
