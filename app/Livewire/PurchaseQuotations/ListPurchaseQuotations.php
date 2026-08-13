<?php

namespace App\Livewire\PurchaseQuotations;

use App\Models\PurchaseQuotation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPurchaseQuotations extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['quotation_number', 'supplier', 'item_count', 'locale', 'created_by', 'created_at'];

    public string $search = '';

    public string $localeFilter = '';

    #[On('purchase-quotations-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('purchase-quotations-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('purchase-quotations-filter')]
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

    #[On('purchase-quotations-clear-filters')]
    public function clearFilters(): void
    {
        $this->localeFilter = '';
        $this->resetPage();
    }

    #[On('purchase-quotations-column-toggle')]
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
            $this->visibleColumns = ['quotation_number'];
        }

        $this->dispatch('purchase-quotations-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('purchase_quotations.actions.add'),
            resource: __('agricart.nav.purchasing.purchase-quotations'),
            formKey: 'purchase-quotations',
        );
    }

    public function openView(int $quotationId): void
    {
        $quotation = PurchaseQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('purchase_quotations.actions.view_short') . ': ' . $quotation->quotation_number,
            resource: __('agricart.nav.purchasing.purchase-quotations'),
            formKey: 'purchase-quotations',
            quotationId: $quotationId,
            mode: 'view',
        );
    }

    public function openEdit(int $quotationId): void
    {
        $quotation = PurchaseQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('purchase_quotations.actions.edit_short') . ': ' . $quotation->quotation_number,
            resource: __('agricart.nav.purchasing.purchase-quotations'),
            formKey: 'purchase-quotations',
            quotationId: $quotationId,
            mode: 'edit',
        );
    }

    public function deleteQuotation(int $quotationId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $quotation = PurchaseQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        app(\App\Services\Purchasing\PurchaseQuotationService::class)->delete($quotation);
        $this->resetPage();
    }

    public function printQuotation(int $quotationId): void
    {
        $quotation = PurchaseQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        $this->dispatch('purchase-quotations-open-print', url: route('admin.purchase-quotations.print', $quotation));
    }

    public function render(): View
    {
        $query = PurchaseQuotation::query()
            ->with(['supplier', 'creator'])
            ->withCount('items')
            ->latest();

        if ($this->search !== '') {
            $needle = '%' . $this->search . '%';
            $query->where(function ($builder) use ($needle): void {
                $builder->where('quotation_number', 'like', $needle)
                    ->orWhereHas('supplier', fn ($q) => $q->where('business_name_en', 'like', $needle)->orWhere('business_name_ur', 'like', $needle));
            });
        }

        if ($this->localeFilter !== '') {
            $query->where('locale', $this->localeFilter);
        }

        return view('livewire.purchase-quotations.list', [
            'quotations' => $query->paginate(15),
            'canCreate' => $this->userCan('create'),
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

        return \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'purchase-quotations', $action);
    }
}
