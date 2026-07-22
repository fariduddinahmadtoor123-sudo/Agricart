<x-filament-panels::page>
    @script
        <script>
            Livewire.on('staff-inbox-clear-search', () => {
                const input = document.getElementById('agricart-sales-search-inbox');

                if (input) {
                    input.value = '';
                }
            });
        </script>
    @endscript

    <livewire:staff.list-inbox />
</x-filament-panels::page>
