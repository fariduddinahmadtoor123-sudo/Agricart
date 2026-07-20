<x-filament-panels::page>
    @script
        <script>
            const syncActivityLogColumnCheckboxes = (columns) => {
                document.querySelectorAll('[data-activity-log-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            };

            const bindActivityLogsToolbarSearch = () => {
                const input = document.getElementById('agricart-settings-search-activity');

                if (! input || input.dataset.activityLogsSearchBound === 'true') {
                    return;
                }

                input.dataset.activityLogsSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('activity-logs-search', {
                        query: event.target.value,
                    });
                });
            };

            bindActivityLogsToolbarSearch();
            document.addEventListener('livewire:navigated', bindActivityLogsToolbarSearch);

            Livewire.on('activity-logs-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                syncActivityLogColumnCheckboxes(columns);
            });
        </script>
    @endscript

    <livewire:activity-logs.list-logs />
</x-filament-panels::page>
