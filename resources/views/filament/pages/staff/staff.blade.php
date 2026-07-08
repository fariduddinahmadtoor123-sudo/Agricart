<x-filament-panels::page>
    @script
        <script>
            Livewire.on('staff-members-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-staff-member-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:staff.list-staff-members />
</x-filament-panels::page>
