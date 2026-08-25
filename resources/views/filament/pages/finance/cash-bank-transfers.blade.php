<x-filament-panels::page>
    @script
        <script>
            Livewire.on('finance-fund-transfers-open-print', (payload) => {
                const url = payload?.url ?? payload?.[0]?.url;

                if (url) {
                    window.open(url, '_blank');
                }
            });
        </script>
    @endscript

    <livewire:finance.list-fund-transfers />
</x-filament-panels::page>
