<x-filament-panels::page>
    @script
        <script>
            Livewire.on('staff-notes-clear-search', () => {
                const input = document.getElementById('agricart-dashboard-search-notes');

                if (input) {
                    input.value = '';
                }
            });
        </script>
    @endscript

    <livewire:dashboard.list-staff-notes />
</x-filament-panels::page>
