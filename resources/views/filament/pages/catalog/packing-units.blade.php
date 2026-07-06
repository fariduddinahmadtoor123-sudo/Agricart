<x-filament-panels::page>
    @script
        <script>
            const bindPackingUnitsToolbarSearch = () => {
                const input = document.getElementById('agricart-catalog-search-packing-units');

                if (! input || input.dataset.packingUnitsSearchBound === 'true') {
                    return;
                }

                input.dataset.packingUnitsSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('packing-units-search', {
                        query: event.target.value,
                    });
                });
            };

            bindPackingUnitsToolbarSearch();
            document.addEventListener('livewire:navigated', bindPackingUnitsToolbarSearch);

            Livewire.on('packing-units-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-packing-unit-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:packing-units.list-packing-units />
</x-filament-panels::page>
