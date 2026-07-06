<x-filament-panels::page>
    @script
        <script>
            const bindControlsToolbarSearch = () => {
                const input = document.getElementById('agricart-catalog-search-controls');

                if (! input || input.dataset.controlsSearchBound === 'true') {
                    return;
                }

                input.dataset.controlsSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('controls-search', {
                        query: event.target.value,
                    });
                });
            };

            bindControlsToolbarSearch();
            document.addEventListener('livewire:navigated', bindControlsToolbarSearch);

            Livewire.on('controls-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-control-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:controls.list-controls />
</x-filament-panels::page>
