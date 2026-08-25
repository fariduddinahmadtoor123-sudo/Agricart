<x-filament-panels::page>
    @script
        <script>
            Livewire.on('finance-expense-payments-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-finance-expense-payment-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });

            Livewire.on('finance-expense-payments-open-print', (payload) => {
                const url = payload?.url ?? payload?.[0]?.url;

                if (url) {
                    window.open(url, '_blank');
                }
            });
        </script>
    @endscript

    <livewire:finance.list-expense-payments />
</x-filament-panels::page>
