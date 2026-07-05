<x-filament-panels::page>
    @script
        <script>
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
        </script>
    @endscript

    <livewire:ai-logs.list-logs />
</x-filament-panels::page>
