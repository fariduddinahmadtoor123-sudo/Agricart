<?php

namespace App\Livewire\PurchasePaymentSummaries;

use App\Models\PurchasePaymentSummary;
use App\Models\User;
use App\Services\Purchasing\PurchasePaymentSummaryService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPaymentSummaries extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['summary_number', 'summary_date', 'paid_lines', 'total_payments', 'locale', 'created_at'];

    public string $search = '';

    public string $localeFilter = '';

    #[On('purchase-payment-summaries-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('purchase-payment-summaries-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('purchase-payment-summaries-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'locale') {
            $this->localeFilter = $value;
            $this->resetPage();
        }
    }

    #[On('purchase-payment-summaries-clear-filters')]
    public function clearFilters(): void
    {
        $this->localeFilter = '';
        $this->resetPage();
    }

    #[On('purchase-payment-summaries-column-toggle')]
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
            $this->visibleColumns = ['summary_number'];
        }

        $this->dispatch('purchase-payment-summaries-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $summaryId): void
    {
        $summary = PurchasePaymentSummary::query()->find($summaryId);

        if ($summary === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('purchase_payment_summary.actions.view', ['number' => $summary->summary_number]),
            resource: __('agricart.pages.purchasing.purchase-payment-summary'),
            formKey: 'purchase-payment-summary',
            summaryId: $summaryId,
            mode: 'view',
        );
    }

    public function openEdit(int $summaryId): void
    {
        $summary = PurchasePaymentSummary::query()->find($summaryId);

        if ($summary === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('purchase_payment_summary.actions.edit', ['number' => $summary->summary_number]),
            resource: __('agricart.pages.purchasing.purchase-payment-summary'),
            formKey: 'purchase-payment-summary',
            summaryId: $summaryId,
            mode: 'edit',
        );
    }

    public function printSummary(int $summaryId): void
    {
        $summary = PurchasePaymentSummary::query()->find($summaryId);

        if ($summary === null) {
            return;
        }

        $this->dispatch('purchase-payment-summary-open-print', url: route('admin.purchase-payment-summaries.print', $summary));
    }

    public function deleteSummary(int $summaryId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $summary = PurchasePaymentSummary::query()->find($summaryId);

        if ($summary === null) {
            return;
        }

        app(PurchasePaymentSummaryService::class)->delete($summary);
        $this->resetPage();
    }

    public function render(): View
    {
        $summaries = PurchasePaymentSummary::query()
            ->with(['items', 'creator'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('summary_number', 'like', $needle)
                        ->orWhere('notes', 'like', $needle);
                });
            })
            ->when(filled($this->localeFilter), fn ($query) => $query->where('locale', $this->localeFilter))
            ->latest('summary_date')
            ->latest('id')
            ->paginate(15);

        return view('livewire.purchase-payment-summaries.list', [
            'summaries' => $summaries,
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
        ]);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'purchase-payment-summary', $action);
    }
}
