<?php

namespace App\Livewire\Reports;

use App\Services\Reports\ReportCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ReportShell extends Component
{
    public string $reportKey = '';

    public string $search = '';

    public string $periodFilter = '';

    public string $secondaryFilter = '';

    /** @var list<string> */
    public array $visibleColumns = [];

    public ?string $statusMessage = null;

    public string $statusType = 'info';

    public function mount(string $reportKey): void
    {
        $this->reportKey = $reportKey;
        $definition = ReportCatalog::definition($reportKey);
        $this->visibleColumns = $definition['defaultVisibleColumns'] ?? ['product'];
    }

    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function clearFiltersAndSearch(): void
    {
        $this->clearFilters();
    }

    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'period') {
            $this->periodFilter = $value;
        } else {
            $this->secondaryFilter = $filter;
        }
    }

    public function clearFilters(): void
    {
        $this->periodFilter = '';
        $this->secondaryFilter = '';
        $this->search = '';
    }

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

        $this->dispatch($this->eventPrefix() . '-columns-sync', columns: $this->visibleColumns);
    }

    public function handleToolbarAction(string $action): void
    {
        if ($action === 'view_report') {
            $this->viewReport();
        }
    }

    public function viewReport(): void
    {
        $this->statusMessage = __('reports.messages.view_report_pending');
        $this->statusType = 'info';
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        $filters = [];

        if (filled($this->periodFilter)) {
            $filters[] = [
                'field' => 'period',
                'label' => __('reports.filters.active_period', [
                    'period' => __('reports.filters.period_' . $this->periodFilter),
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
        return filled($this->search)
            || filled($this->periodFilter)
            || filled($this->secondaryFilter);
    }

    public function removeFilter(string $field): void
    {
        match ($field) {
            'period' => $this->periodFilter = '',
            'secondary' => $this->secondaryFilter = '',
            default => null,
        };
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
            'columnDataAttr' => 'data-report-' . str_replace('-', '-', $this->reportKey) . '-column',
        ]);
    }
}
