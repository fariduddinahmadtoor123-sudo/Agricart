<?php

namespace App\Livewire\Reports;

use App\Services\Reports\CustomerLedgerReportService;
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\StockReportService;
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
            $this->statusMessage = __($this->isStockReport() ? 'reports.messages.stock_loaded' : 'reports.messages.customer_ledger_loaded', [
                'count' => count($this->rows),
            ]);
            $this->statusType = 'success';

            return;
        }

        $this->statusMessage = __('reports.messages.view_report_pending');
        $this->statusType = 'info';
    }

    public function openCustomerLedger(int $customerId): void
    {
        if (! $this->isCustomerLedgerReport()) {
            return;
        }

        $this->ledgerDetail = app(CustomerLedgerReportService::class)->history(
            $customerId,
            $this->periodFilter !== '' ? $this->periodFilter : null,
        );
        $this->ledgerDetailOpen = true;
    }

    public function closeCustomerLedger(): void
    {
        $this->ledgerDetailOpen = false;
        $this->ledgerDetail = null;
    }

    public function exportCsv(): ?StreamedResponse
    {
        if (! $this->isLiveReport()) {
            return null;
        }

        if (! $this->reportLoaded) {
            $this->loadLiveReport();
        }

        $matrix = $this->isStockReport()
            ? app(StockReportService::class)->csvMatrix($this->rows, $this->visibleColumns)
            : app(CustomerLedgerReportService::class)->csvMatrix($this->rows, $this->visibleColumns);
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

    protected function isLiveReport(): bool
    {
        return $this->isStockReport() || $this->isCustomerLedgerReport();
    }

    protected function loadLiveReport(): void
    {
        if ($this->isStockReport()) {
            $this->loadStockReport();

            return;
        }

        if ($this->isCustomerLedgerReport()) {
            $this->loadCustomerLedgerReport();
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
            'isLiveReport' => $this->isLiveReport(),
        ]);
    }
}
