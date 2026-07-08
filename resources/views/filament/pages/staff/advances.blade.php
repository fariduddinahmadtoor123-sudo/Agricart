<x-filament-panels::page>
    @script
        <script>
            Livewire.on('staff-advances-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-staff-advances-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:staff.list-staff-advances />
</x-filament-panels::page>
