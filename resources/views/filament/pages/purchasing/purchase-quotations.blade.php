<x-filament-panels::page>
    @script
        <script>
            Livewire.on('purchase-quotations-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-quotation-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:purchase-quotations.list-purchase-quotations />
</x-filament-panels::page>
