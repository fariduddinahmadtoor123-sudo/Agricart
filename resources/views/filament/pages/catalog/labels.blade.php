<x-filament-panels::page>
    @script
        <script>
            const bindLabelsToolbarSearch = () => {
                const input = document.getElementById('agricart-catalog-search-labels');

                if (! input || input.dataset.labelsSearchBound === 'true') {
                    return;
                }

                input.dataset.labelsSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('labels-search', {
                        query: event.target.value,
                    });
                });
            };

            bindLabelsToolbarSearch();
            document.addEventListener('livewire:navigated', bindLabelsToolbarSearch);
        </script>
    @endscript

    <livewire:labels.list-labels />
</x-filament-panels::page>
