<x-filament-panels::page>
    @script
        <script>
            Livewire.on('finance-expense-payments-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-finance-expense-payment-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:finance.list-expense-payments />
</x-filament-panels::page>
