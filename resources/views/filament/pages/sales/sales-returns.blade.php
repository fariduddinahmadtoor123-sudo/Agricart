<x-filament-panels::page>
    @script
        <script>
            Livewire.on('sale-returns-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-sale-return-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:sale-returns.list-sale-returns />
</x-filament-panels::page>
