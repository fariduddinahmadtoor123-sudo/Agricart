<x-filament-panels::page>
    @script
        <script>
            Livewire.on('staff-leave-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-staff-leave-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:staff.list-staff-leave-report />
</x-filament-panels::page>
