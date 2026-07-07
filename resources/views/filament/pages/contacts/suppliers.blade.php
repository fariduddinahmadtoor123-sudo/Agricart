<x-filament-panels::page>
    @script
        <script>
            Livewire.on('suppliers-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-supplier-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:suppliers.list-suppliers />
</x-filament-panels::page>
