<?php

namespace App\Livewire\Reports;

use App\Services\Reports\CustomerLedgerReportService;
use App\Services\Reports\ProfitLossReportService;
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\SalesSummaryReportService;
use App\Services\Reports\StockReportService;
use App\Services\Reports\SupplierLedgerReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportShell extends Component
{
    public string $reportKey = '';

    public string $search = '';

    public string $periodFilter = '';

    public string $secondaryFilter = '';

    public string $stockFilter = 'all';

    public string $categoryFilter = 'all';

    public string $channelFilter = 'all';

    public string $groupByFilter = 'day';

    public string $resultFilter = 'all';

    /** @var list<string> */
    public array $visibleColumns = [];

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, int|float|string> */
    public array $kpis = [];

    public bool $reportLoaded = false;

    public ?string $statusMessage = null;

    public string $statusType = 'info';

    public bool $ledgerDetailOpen = false;

    /** @var array<string, mixed>|null */
    public ?array $ledgerDetail = null;

    public function mount(string $reportKey): void
    {
        $this->reportKey = $reportKey;
        $definition = ReportCatalog::definition($reportKey);
        $this->visibleColumns = $definition['defaultVisibleColumns'] ?? ['product'];

        if ($this->isLiveReport()) {
            $this->loadLiveReport();
        }
    }

    #[On('updateSearch')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;

        if ($this->isLiveReport()) {
            $this->loadLiveReport();
        }
    }

    #[On('clearSearch')]
    public function clearSearch(): void
    {
        $this->search = '';

        if ($this->isLiveReport()) {
            $this->loadLiveReport();
        }
    }

    public function clearFiltersAndSearch(): void
    {
        $this->clearFilters();
    }

    #[On('applyFilter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($this->isStockReport()) {
            if ($field === 'stock') {
                $this->stockFilter = $value;
            } elseif ($field === 'category') {
                $this->categoryFilter = $value;
            }

            $this->loadStockReport();

            return;
        }

        if ($this->isSalesSummaryReport()) {
            if ($field === 'period') {
                $this->periodFilter = $value;
            } elseif ($field === 'channel') {
                $this->channelFilter = $value;
            } elseif ($field === 'group') {
                $this->groupByFilter = $value;
            }

            $this->loadSalesSummaryReport();

            return;
        }

        if ($this->isProfitLossReport()) {
            if ($field === 'period') {
                $this->periodFilter = $value;
            } elseif ($field === 'result') {
                $this->resultFilter = $value;
            }

            $this->loadProfitLossReport();

            return;
        }

        if ($field === 'period') {
            $this->periodFilter = $value;
        } else {
            $this->secondaryFilter = $filter;
        }

        if ($this->isLiveReport()) {
            $this->loadLiveReport();
        }
    }

    #[On('clearFilters')]
    public function clearFilters(): void
    {
        $this->periodFilter = '';
        $this->secondaryFilter = '';
        $this->stockFilter = 'all';
        $this->categoryFilter = 'all';
        $this->channelFilter = 'all';
        $this->groupByFilter = 'day';
        $this->resultFilter = 'all';
        $this->search = '';

        if ($this->isLiveReport()) {
            $this->loadLiveReport();
        }
    }

    #[On('toggleColumn')]
    public function toggleColumn(string $column, bool $visible): void
    {
        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $col): bool => $col !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = [$this->columnDefinitions()[0]['value'] ?? 'product'];
        }

        $this->dispatch($this->eventPrefix().'-columns-sync', columns: $this->visibleColumns);
    }

    #[On('handleToolbarAction')]
    public function handleToolbarAction(string $action): mixed
    {
        if ($action === 'view_report') {
            $this->viewReport();

            return null;
        }

        if ($action === 'export_csv') {
            return $this->exportCsv();
        }

        return null;
    }

    public function viewReport(): void
    {
        if ($this->isLiveReport()) {
            $this->loadLiveReport();
            $messageKey = match (true) {
                $this->isStockReport() => 'reports.messages.stock_loaded',
                $this->isSalesSummaryReport() => 'reports.messages.sales_summary_loaded',
                $this->isProfitLossReport() => 'reports.messages.profit_loss_loaded',
                $this->isSupplierLedgerReport() => 'reports.messages.supplier_ledger_loaded',
                default => 'reports.messages.customer_ledger_loaded',
            };
            $this->statusMessage = __($messageKey, [
                'count' => count($this->rows),
            ]);
            $this->statusType = 'success';

            return;
        }

        $this->statusMessage = __('reports.messages.view_report_pending');
        $this->statusType = 'info';
    }

    public function openLedgerDetail(int $partyId): void
    {
        if ($this->isCustomerLedgerReport()) {
            $this->ledgerDetail = app(CustomerLedgerReportService::class)->history(
                $partyId,
                $this->periodFilter !== '' ? $this->periodFilter : null,
            );
            $this->ledgerDetailOpen = true;

            return;
        }

        if ($this->isSupplierLedgerReport()) {
            $this->ledgerDetail = app(SupplierLedgerReportService::class)->history(
                $partyId,
                $this->periodFilter !== '' ? $this->periodFilter : null,
            );
            $this->ledgerDetailOpen = true;
        }
    }

    public function openCustomerLedger(int $customerId): void
    {
        $this->openLedgerDetail($customerId);
    }

    public function closeLedgerDetail(): void
    {
        $this->ledgerDetailOpen = false;
        $this->ledgerDetail = null;
    }

    public function closeCustomerLedger(): void
    {
        $this->closeLedgerDetail();
    }

    public function exportCsv(): ?StreamedResponse
    {
        if (! $this->isLiveReport()) {
            return null;
        }

        if (! $this->reportLoaded) {
            $this->loadLiveReport();
        }

        $matrix = match (true) {
            $this->isStockReport() => app(StockReportService::class)->csvMatrix($this->rows, $this->visibleColumns),
            $this->isSalesSummaryReport() => app(SalesSummaryReportService::class)->csvMatrix($this->rows, $this->visibleColumns),
            $this->isProfitLossReport() => app(ProfitLossReportService::class)->csvMatrix($this->rows, $this->visibleColumns),
            $this->isSupplierLedgerReport() => app(SupplierLedgerReportService::class)->csvMatrix($this->rows, $this->visibleColumns),
            default => app(CustomerLedgerReportService::class)->csvMatrix($this->rows, $this->visibleColumns),
        };
        $filename = $this->reportKey.'-'.now()->format('Y_m_d_His').'.csv';

        return response()->streamDownload(function () use ($matrix): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($matrix as $line) {
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        $filters = [];

        if ($this->isStockReport()) {
            if (filled($this->stockFilter) && $this->stockFilter !== 'all') {
                $filters[] = [
                    'field' => 'stock',
                    'label' => __('reports.filters.active_stock', [
                        'status' => __('reports.filters.stock_'.$this->stockFilter),
                    ]),
                ];
            }

            if (filled($this->categoryFilter) && $this->categoryFilter !== 'all') {
                $filters[] = [
                    'field' => 'category',
                    'label' => $this->categoryFilterLabel(),
                ];
            }

            return $filters;
        }

        if ($this->isSalesSummaryReport()) {
            if (filled($this->periodFilter)) {
                $filters[] = [
                    'field' => 'period',
                    'label' => __('reports.filters.active_period', [
                        'period' => __('reports.filters.period_'.$this->periodFilter),
                    ]),
                ];
            }

            if (filled($this->channelFilter) && $this->channelFilter !== 'all') {
                $filters[] = [
                    'field' => 'channel',
                    'label' => __('reports.filters.active_channel', [
                        'channel' => __('reports.filters.channel_'.$this->channelFilter),
                    ]),
                ];
            }

            if (filled($this->groupByFilter) && $this->groupByFilter !== 'day') {
                $filters[] = [
                    'field' => 'group',
                    'label' => __('reports.filters.active_group', [
                        'group' => __('reports.filters.group_'.$this->groupByFilter),
                    ]),
                ];
            }

            return $filters;
        }

        if ($this->isProfitLossReport()) {
            if (filled($this->periodFilter)) {
                $filters[] = [
                    'field' => 'period',
                    'label' => __('reports.filters.active_period', [
                        'period' => __('reports.filters.period_'.$this->periodFilter),
                    ]),
                ];
            }

            if (filled($this->resultFilter) && $this->resultFilter !== 'all') {
                $filters[] = [
                    'field' => 'result',
                    'label' => __('reports.filters.active_result', [
                        'result' => __('reports.filters.result_'.$this->resultFilter),
                    ]),
                ];
            }

            return $filters;
        }

        if (filled($this->periodFilter)) {
            $filters[] = [
                'field' => 'period',
                'label' => __('reports.filters.active_period', [
                    'period' => __('reports.filters.period_'.$this->periodFilter),
                ]),
            ];
        }

        if (filled($this->secondaryFilter)) {
            $filters[] = [
                'field' => 'secondary',
                'label' => $this->secondaryFilterLabel(),
            ];
        }

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        if ($this->isStockReport()) {
            return filled($this->search)
                || ($this->stockFilter !== '' && $this->stockFilter !== 'all')
                || ($this->categoryFilter !== '' && $this->categoryFilter !== 'all');
        }

        if ($this->isSalesSummaryReport()) {
            return filled($this->search)
                || filled($this->periodFilter)
                || ($this->channelFilter !== '' && $this->channelFilter !== 'all')
                || ($this->groupByFilter !== '' && $this->groupByFilter !== 'day');
        }

        if ($this->isProfitLossReport()) {
            return filled($this->search)
                || filled($this->periodFilter)
                || ($this->resultFilter !== '' && $this->resultFilter !== 'all');
        }

        return filled($this->search)
            || filled($this->periodFilter)
            || filled($this->secondaryFilter);
    }

    public function removeFilter(string $field): void
    {
        if ($this->isStockReport()) {
            match ($field) {
                'stock' => $this->stockFilter = 'all',
                'category' => $this->categoryFilter = 'all',
                default => null,
            };
            $this->loadStockReport();

            return;
        }

        if ($this->isSalesSummaryReport()) {
            match ($field) {
                'period' => $this->periodFilter = '',
                'channel' => $this->channelFilter = 'all',
                'group' => $this->groupByFilter = 'day',
                default => null,
            };
            $this->loadSalesSummaryReport();

            return;
        }

        if ($this->isProfitLossReport()) {
            match ($field) {
                'period' => $this->periodFilter = '',
                'result' => $this->resultFilter = 'all',
                default => null,
            };
            $this->loadProfitLossReport();

            return;
        }

        match ($field) {
            'period' => $this->periodFilter = '',
            'secondary' => $this->secondaryFilter = '',
            default => null,
        };

        if ($this->isLiveReport()) {
            $this->loadLiveReport();
        }
    }

    /**
     * @return list<array{label: string, value: string, visible: bool}>
     */
    public function columnDefinitions(): array
    {
        return ReportCatalog::definition($this->reportKey)['columns'] ?? [];
    }

    protected function eventPrefix(): string
    {
        return ReportCatalog::eventPrefix($this->reportKey);
    }

    protected function isStockReport(): bool
    {
        return $this->reportKey === 'stock-report';
    }

    protected function isCustomerLedgerReport(): bool
    {
        return $this->reportKey === 'customer-ledger';
    }

    protected function isSupplierLedgerReport(): bool
    {
        return $this->reportKey === 'supplier-ledger';
    }

    protected function isSalesSummaryReport(): bool
    {
        return $this->reportKey === 'sales-summary';
    }

    protected function isProfitLossReport(): bool
    {
        return $this->reportKey === 'profit-loss';
    }

    protected function isPartyLedgerReport(): bool
    {
        return $this->isCustomerLedgerReport() || $this->isSupplierLedgerReport();
    }

    protected function isLiveReport(): bool
    {
        return $this->isStockReport()
            || $this->isPartyLedgerReport()
            || $this->isSalesSummaryReport()
            || $this->isProfitLossReport();
    }

    protected function loadLiveReport(): void
    {
        if ($this->isStockReport()) {
            $this->loadStockReport();

            return;
        }

        if ($this->isSalesSummaryReport()) {
            $this->loadSalesSummaryReport();

            return;
        }

        if ($this->isProfitLossReport()) {
            $this->loadProfitLossReport();

            return;
        }

        if ($this->isCustomerLedgerReport()) {
            $this->loadCustomerLedgerReport();

            return;
        }

        if ($this->isSupplierLedgerReport()) {
            $this->loadSupplierLedgerReport();
        }
    }

    protected function loadStockReport(): void
    {
        $payload = app(StockReportService::class)->build(
            $this->search !== '' ? $this->search : null,
            $this->stockFilter !== '' ? $this->stockFilter : 'all',
            $this->categoryFilter !== '' ? $this->categoryFilter : 'all',
        );

        $this->rows = $payload['rows'];
        $this->kpis = $payload['kpis'];
        $this->reportLoaded = true;
    }

    protected function loadCustomerLedgerReport(): void
    {
        $partyFilter = 'all';

        if (str_starts_with($this->secondaryFilter, 'customer:')) {
            [, $partyFilter] = explode(':', $this->secondaryFilter, 2);
        }

        $payload = app(CustomerLedgerReportService::class)->build(
            $this->search !== '' ? $this->search : null,
            $this->periodFilter !== '' ? $this->periodFilter : null,
            $partyFilter !== '' ? $partyFilter : 'all',
        );

        $this->rows = $payload['rows'];
        $this->kpis = $payload['kpis'];
        $this->reportLoaded = true;
    }

    protected function loadSupplierLedgerReport(): void
    {
        $partyFilter = 'all';

        if (str_starts_with($this->secondaryFilter, 'supplier:')) {
            [, $partyFilter] = explode(':', $this->secondaryFilter, 2);
        }

        $payload = app(SupplierLedgerReportService::class)->build(
            $this->search !== '' ? $this->search : null,
            $this->periodFilter !== '' ? $this->periodFilter : null,
            $partyFilter !== '' ? $partyFilter : 'all',
        );

        $this->rows = $payload['rows'];
        $this->kpis = $payload['kpis'];
        $this->reportLoaded = true;
    }

    protected function loadSalesSummaryReport(): void
    {
        $payload = app(SalesSummaryReportService::class)->build(
            $this->search !== '' ? $this->search : null,
            $this->periodFilter !== '' ? $this->periodFilter : null,
            $this->channelFilter !== '' ? $this->channelFilter : 'all',
            $this->groupByFilter !== '' ? $this->groupByFilter : 'day',
        );

        $this->rows = $payload['rows'];
        $this->kpis = $payload['kpis'];
        $this->reportLoaded = true;
    }

    protected function loadProfitLossReport(): void
    {
        $payload = app(ProfitLossReportService::class)->build(
            $this->search !== '' ? $this->search : null,
            $this->periodFilter !== '' ? $this->periodFilter : null,
            $this->resultFilter !== '' ? $this->resultFilter : 'all',
        );

        $this->rows = $payload['rows'];
        $this->kpis = $payload['kpis'];
        $this->reportLoaded = true;
    }

    protected function categoryFilterLabel(): string
    {
        $needle = 'category:'.$this->categoryFilter;
        $definition = ReportCatalog::definition($this->reportKey);

        foreach (collect($definition['filterGroups'] ?? [])->flatMap(fn (array $g) => $g['filters'] ?? []) as $option) {
            if (($option['value'] ?? '') === $needle) {
                return __('reports.filters.active_category', [
                    'category' => (string) ($option['label'] ?? $this->categoryFilter),
                ]);
            }
        }

        return __('reports.filters.active_category', ['category' => $this->categoryFilter]);
    }

    protected function secondaryFilterLabel(): string
    {
        $filter = $this->secondaryFilter;
        $definition = ReportCatalog::definition($this->reportKey);

        if ($definition === null) {
            return $filter;
        }

        foreach ([...($definition['filters'] ?? []), ...collect($definition['filterGroups'] ?? [])->flatMap(fn (array $g) => $g['filters'] ?? [])] as $option) {
            if (($option['value'] ?? '') === $filter) {
                return (string) ($option['label'] ?? $filter);
            }
        }

        return $filter;
    }

    public function render(): View
    {
        return view('livewire.reports.report-shell', [
            'columns' => $this->columnDefinitions(),
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'columnDataAttr' => 'data-report-'.str_replace('-', '-', $this->reportKey).'-column',
            'isStockReport' => $this->isStockReport(),
            'isCustomerLedgerReport' => $this->isCustomerLedgerReport(),
            'isSupplierLedgerReport' => $this->isSupplierLedgerReport(),
            'isSalesSummaryReport' => $this->isSalesSummaryReport(),
            'isProfitLossReport' => $this->isProfitLossReport(),
            'isPartyLedgerReport' => $this->isPartyLedgerReport(),
            'isLiveReport' => $this->isLiveReport(),
        ]);
    }
}
