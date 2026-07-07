<x-filament-panels::page>
    @script
        <script>
            Livewire.on('purchases-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-purchase-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:purchases.list-purchases />
</x-filament-panels::page>
