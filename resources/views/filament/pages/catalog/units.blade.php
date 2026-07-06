<x-filament-panels::page>
    @script
        <script>
            const bindUnitsToolbarSearch = () => {
                const input = document.getElementById('agricart-catalog-search-units');

                if (! input || input.dataset.unitsSearchBound === 'true') {
                    return;
                }

                input.dataset.unitsSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('units-search', {
                        query: event.target.value,
                    });
                });
            };

            bindUnitsToolbarSearch();
            document.addEventListener('livewire:navigated', bindUnitsToolbarSearch);

            Livewire.on('units-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-unit-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:units.list-units />
</x-filament-panels::page>
