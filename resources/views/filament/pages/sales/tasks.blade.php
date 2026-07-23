<x-filament-panels::page>
    @script
        <script>
            Livewire.on('task-inbox-clear-search', () => {
                const input = document.getElementById('agricart-sales-search-tasks');

                if (input) {
                    input.value = '';
                }
            });
        </script>
    @endscript

    <livewire:communication.list-task-inbox />
</x-filament-panels::page>
