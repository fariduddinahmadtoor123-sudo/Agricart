<x-filament-panels::page>
    @script
        <script>
            const bindProductsToolbarSearch = () => {
                const input = document.getElementById('agricart-catalog-search-products');

                if (! input || input.dataset.productsSearchBound === 'true') {
                    return;
                }

                input.dataset.productsSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('products-search', {
                        query: event.target.value,
                    });
                });
            };

            bindProductsToolbarSearch();
            document.addEventListener('livewire:navigated', bindProductsToolbarSearch);

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
