<x-filament-panels::page>
    @script
        <script>
            Livewire.on('pay-management-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-pay-management-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:staff.list-pay-management />
</x-filament-panels::page>
