<x-filament-panels::page>
    @script
        <script>
            Livewire.on('transport-companies-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-transport-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:transport-companies.list-transport-companies />
</x-filament-panels::page>
