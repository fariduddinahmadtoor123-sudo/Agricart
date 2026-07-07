<x-filament-panels::page>
    @script
        <script>
            Livewire.on('packing-units-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-packing-unit-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:packing-units.list-packing-units />
</x-filament-panels::page>
