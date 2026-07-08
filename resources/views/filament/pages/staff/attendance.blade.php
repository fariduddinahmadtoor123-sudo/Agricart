<x-filament-panels::page>
    @script
        <script>
            Livewire.on('staff-attendance-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-staff-attendance-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:staff.list-daily-attendance />
</x-filament-panels::page>
