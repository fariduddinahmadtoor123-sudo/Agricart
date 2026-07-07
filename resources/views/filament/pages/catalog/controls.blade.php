<x-filament-panels::page>
    @script
        <script>
            Livewire.on('controls-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-control-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:controls.list-controls />
</x-filament-panels::page>
