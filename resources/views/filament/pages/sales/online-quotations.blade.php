<x-filament-panels::page>
    @script
        <script>
            Livewire.on('online-quotations-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-online-quotation-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:online-sale-orders.list-online-sale-quotations />
</x-filament-panels::page>
