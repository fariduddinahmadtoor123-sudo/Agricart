<x-filament-panels::page>
    @script
        <script>
            Livewire.on('finance-expenses-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-finance-expense-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:finance.list-expenses />
</x-filament-panels::page>
