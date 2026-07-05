<x-filament-panels::page>
    @script
        <script>
            const syncAiLogColumnCheckboxes = (columns) => {
                document.querySelectorAll('[data-ai-log-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            };

            const bindAiLogsToolbarSearch = () => {
                const input = document.getElementById('agricart-settings-search-ai-logs');

                if (! input || input.dataset.aiLogsSearchBound === 'true') {
                    return;
                }

                input.dataset.aiLogsSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('ai-logs-search', {
                        query: event.target.value,
                    });
                });
            };

            bindAiLogsToolbarSearch();
            document.addEventListener('livewire:navigated', bindAiLogsToolbarSearch);

            Livewire.on('ai-logs-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                syncAiLogColumnCheckboxes(columns);
            });
        </script>
    @endscript

    <livewire:ai-logs.list-logs />
</x-filament-panels::page>
