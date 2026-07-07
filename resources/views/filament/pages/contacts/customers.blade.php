<x-filament-panels::page>
    @script
        <script>
            Livewire.on('customers-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-customer-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:customers.list-customers />
</x-filament-panels::page>
