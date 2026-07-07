<x-filament-panels::page>
    @script
        <script>
            Livewire.on('units-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-unit-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:units.list-units />
</x-filament-panels::page>
