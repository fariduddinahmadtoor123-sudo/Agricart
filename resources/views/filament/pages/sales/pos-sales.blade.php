<x-filament-panels::page>
    @script
        <script>
            Livewire.on('pos-sales-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-sale-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:sales.list-sales />
</x-filament-panels::page>
