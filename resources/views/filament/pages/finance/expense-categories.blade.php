<x-filament-panels::page>
    @script
        <script>
            Livewire.on('finance-expense-categories-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-finance-expense-category-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:finance.list-expense-categories />
</x-filament-panels::page>
