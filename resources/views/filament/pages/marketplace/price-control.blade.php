<x-filament-panels::page>
    @script
        <script>
            Livewire.on('price-controls-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-price-control-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:price-controls.list-price-controls />
</x-filament-panels::page>
