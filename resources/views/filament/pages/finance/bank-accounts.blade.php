<x-filament-panels::page>
    @script
        <script>
            Livewire.on('finance-banks-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-finance-bank-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });

            Livewire.on('finance-banks-open-print', (payload) => {
                const url = payload?.url ?? payload?.[0]?.url;

                if (url) {
                    window.open(url, '_blank');
                }
            });
        </script>
    @endscript

    <livewire:finance.list-bank-accounts />
</x-filament-panels::page>
