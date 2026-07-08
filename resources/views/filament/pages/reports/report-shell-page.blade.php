<x-filament-panels::page>
    @php
        $reportKey = $this->getReportKey();
        $prefix = \App\Services\Reports\ReportCatalog::eventPrefix($reportKey);
        $columnSelector = '[data-report-' . $reportKey . '-column]';
        $searchInputId = 'agricart-reports-search-' . $reportKey;
    @endphp
    @script
        <script>
            Livewire.on(@js($prefix . '-columns-sync'), (payload) => {
                const columns = payload?.columns ?? payload?.[0]?.columns ?? [];

                document.querySelectorAll(@js($columnSelector)).forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });

            Livewire.on(@js($prefix . '-clear-search'), () => {
                const input = document.getElementById(@js($searchInputId));

                if (input) {
                    input.value = '';
                }
            });
        </script>
    @endscript

    <livewire:reports.report-shell :report-key="$reportKey" :key="'report-shell-' . $reportKey" />
</x-filament-panels::page>
