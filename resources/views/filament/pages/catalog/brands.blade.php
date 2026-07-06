<x-filament-panels::page>
    @script
        <script>
            const bindBrandsToolbarSearch = () => {
                const input = document.getElementById('agricart-catalog-search-brands');

                if (! input || input.dataset.brandsSearchBound === 'true') {
                    return;
                }

                input.dataset.brandsSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('brands-search', {
                        query: event.target.value,
                    });
                });
            };

            bindBrandsToolbarSearch();
            document.addEventListener('livewire:navigated', bindBrandsToolbarSearch);

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
