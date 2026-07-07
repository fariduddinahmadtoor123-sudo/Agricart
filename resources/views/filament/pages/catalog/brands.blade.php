<x-filament-panels::page>
    @script
        <script>
            Livewire.on('brands-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-brand-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:brands.list-brands />
</x-filament-panels::page>
