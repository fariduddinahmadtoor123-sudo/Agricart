<x-filament-panels::page>
    @script
        <script>
            Livewire.on('attributes-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-attribute-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:attributes.list-attributes />
</x-filament-panels::page>
