<x-filament-panels::page>
    @script
        <script>
            Livewire.on('products-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-product-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:products.list-products />
</x-filament-panels::page>
