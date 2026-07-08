<x-filament-panels::page>
    @script
        <script>
            Livewire.on('staff-payroll-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-staff-payroll-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:staff.list-staff-payroll />
</x-filament-panels::page>
