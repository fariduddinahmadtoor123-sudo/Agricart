<x-filament-panels::page>
    @script
        <script>
            Livewire.on('purchase-planning-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-planning-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:purchase-planning.list-planning-sheets />
</x-filament-panels::page>
