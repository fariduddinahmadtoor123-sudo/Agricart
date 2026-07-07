<x-filament-panels::page>
    @script
        <script>
            Livewire.on('sale-quotations-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-sale-quotation-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:sale-quotations.list-sale-quotations />
</x-filament-panels::page>
