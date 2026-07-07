<x-filament-panels::page>
    @script
        <script>
            Livewire.on('quantity-controls-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-quantity-control-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:quantity-controls.list-quantity-controls />
</x-filament-panels::page>
