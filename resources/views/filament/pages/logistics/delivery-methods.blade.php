<x-filament-panels::page>
    @script
        <script>
            Livewire.on('delivery-methods-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-delivery-method-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:delivery-methods.list-methods />
</x-filament-panels::page>
