<x-filament-panels::page>
    @script
        <script>
            const syncSystemErrorColumnCheckboxes = (columns) => {
                document.querySelectorAll('[data-system-error-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            };

            const bindSystemErrorsToolbarSearch = () => {
                const input = document.getElementById('agricart-settings-search-system-errors');

                if (! input || input.dataset.systemErrorsSearchBound === 'true') {
                    return;
                }

                input.dataset.systemErrorsSearchBound = 'true';
                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('system-errors-search', {
                        query: event.target.value,
                    });
                });
            };

            bindSystemErrorsToolbarSearch();
            document.addEventListener('livewire:navigated', bindSystemErrorsToolbarSearch);

            Livewire.on('system-errors-columns-sync', (payload) => {
                syncSystemErrorColumnCheckboxes(payload?.columns ?? []);
            });
        </script>
    @endscript

    <livewire:system-errors.list-errors />
</x-filament-panels::page>
