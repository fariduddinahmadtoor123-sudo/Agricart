<x-filament-panels::page>
    @script
        <script>
            Livewire.on('purchase-payment-summaries-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-payment-summary-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:purchase-payment-summaries.list-payment-summaries />
</x-filament-panels::page>
