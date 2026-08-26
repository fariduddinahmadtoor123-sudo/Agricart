<x-filament-panels::page>
    @script
        <script>
            Livewire.on('packages-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-package-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:packages.list-packages />
</x-filament-panels::page>
