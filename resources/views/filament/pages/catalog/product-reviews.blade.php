<x-filament-panels::page>
    @script
        <script>
            Livewire.on('product-reviews-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-product-review-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:products.list-product-agricart-reviews />
</x-filament-panels::page>
